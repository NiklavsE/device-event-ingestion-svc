<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent;

use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DedupHash;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventTimestamp;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventType;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\GeoPoint;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\Media;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;

final readonly class IncomingEvent
{
    /**
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        public string $protocol,
        public DeviceImei $deviceImei,
        public VehicleId $vehicleId,
        public EventType $eventType,
        public EventTimestamp $eventTimestamp,
        public GeoPoint $location,
        public ?Media $media,
        public DedupHash $dedupHash,
        public array $rawPayload,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'protocol'        => $this->protocol,
            'device_imei'     => $this->deviceImei->value(),
            'vehicle_id'      => $this->vehicleId->value(),
            'event_type'      => $this->eventType->value(),
            'event_timestamp' => $this->eventTimestamp->toIso8601(),
            'latitude'        => $this->location->latitude(),
            'longitude'       => $this->location->longitude(),
            'speed'           => $this->location->speed(),
            'media'           => $this->media?->toArray(),
        ];
    }
}
