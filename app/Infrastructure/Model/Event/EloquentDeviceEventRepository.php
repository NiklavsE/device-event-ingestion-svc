<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Model\Event;

use DeviceEventIngestionService\Domain\DeviceEvent\DeviceEvent;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\DeviceEventAlreadyExists;
use DeviceEventIngestionService\Domain\DeviceEvent\Interface\DeviceEventRepositoryInterface;
use DeviceEventIngestionService\Domain\DeviceEvent\Queries\VehicleEventQuery;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DedupHash;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventTimestamp;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventType;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\GeoPoint;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\Media;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;
use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class EloquentDeviceEventRepository implements DeviceEventRepositoryInterface
{
    public function save(DeviceEvent $event): void
    {
        DB::transaction(function () use ($event): void {
            $deviceId = EloquentDeviceModel::query()
                ->where('imei', $event->deviceImei->value())
                ->value('id');

            try {
                $row = EloquentEventModel::create([
                    'device_id' => $deviceId,
                    'vehicle_external_id' => $event->vehicleId->value(),
                    'protocol' => $event->protocol,
                    'event_type' => $event->eventType->value(),
                    'event_timestamp' => $event->eventTimestamp->toDateTimeImmutable(),
                    'latitude' => $event->location->latitude(),
                    'longitude' => $event->location->longitude(),
                    'speed' => $event->location->speed(),
                    'heading' => $event->location->heading(),
                    'dedup_hash' => $event->dedupHash->value(),
                    'raw_payload' => $event->rawPayload,
                ]);
            } catch (QueryException $e) {
                if (false === $this->isUniqueViolation($e)) {
                    throw $e;
                }
                // DB UNIQUE(dedup_hash) caught a duplicate — surface as the
                // domain-level exception so callers handle this branch the
                // same way regardless of which tier detected the collision.
                throw new DeviceEventAlreadyExists($event->dedupHash);
            }

            if ($event->hasMedia()) {
                EloquentDeviceEventMediaModel::create([
                    'event_id' => $row->id,
                    'channel' => $event->media->channel(),
                    'file_name' => $event->media->fileName(),
                    'duration_seconds' => $event->media->durationSeconds(),
                    'codec' => $event->media->codec(),
                    'media_type' => $event->media->mediaType(),
                ]);
            }
        });
    }

    public function ofVehicleQuery(VehicleEventQuery $criteria): array
    {
        $rows = EloquentEventModel::query()
            ->with(['media', 'device'])
            ->where('vehicle_external_id', $criteria->vehicleId->value())
            ->when(
                $criteria->eventType,
                fn ($q, EventType $type) => $q->where('event_type', $type->value()),
            )
            ->when($criteria->from, fn ($q, $from) => $q->where('event_timestamp', '>=', $from))
            ->when($criteria->to, fn ($q, $to) => $q->where('event_timestamp', '<=', $to))
            ->when($criteria->hasMedia === true, fn ($q) => $q->whereHas('media'))
            ->when($criteria->hasMedia === false, fn ($q) => $q->whereDoesntHave('media'))
            ->orderByDesc('event_timestamp')
            ->orderByDesc('id')
            ->limit($criteria->limit)
            ->get()
            ->all();

        return array_map($this->toDomain(...), $rows);
    }

    private function toDomain(EloquentEventModel $row): DeviceEvent
    {
        return new DeviceEvent(
            $row->protocol,
            DeviceImei::fromString($row->device->imei),
            VehicleId::fromString($row->vehicle_external_id),
            EventType::fromString($row->event_type),
            EventTimestamp::fromIso8601($row->event_timestamp->format('Y-m-d\TH:i:s\Z')),
            GeoPoint::create(
                (float) $row->latitude,
                (float) $row->longitude,
                $row->speed !== null ? (float) $row->speed : null,
                $row->heading,
            ),
            $row->media === null
                ? null
                : Media::create(
                    $row->media->channel,
                    $row->media->file_name,
                    $row->media->duration_seconds,
                    $row->media->codec,
                    $row->media->media_type,
                ),
            DedupHash::fromHex($row->dedup_hash),
            $row->raw_payload,
            $row->id,
        );
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        // SQLSTATE 23000 covers MySQL unique-constraint violations; the
        // vendor code 1062 is MySQL's own signal for the same condition.
        // Either branch is sufficient; both are kept because some PDO
        // drivers populate one but not the other.
        return $e->getCode() === '23000'
            || (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
