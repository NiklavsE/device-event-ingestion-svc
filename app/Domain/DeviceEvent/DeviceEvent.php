<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent;

use DeviceEventIngestionService\Domain\Device\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DedupHash;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventTimestamp;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventType;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\GeoPoint;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\Media;
use DeviceEventIngestionService\Domain\Vehicle\VehicleId;

final readonly class DeviceEvent
{
    /**
     * @param array<string, mixed> $rawPayload
     * @param ?int $deviceId  Surrogate key of the device row; threaded from
     *                        Device::id() to skip an IMEI→id lookup at write
     *                        time. Null only when emitted from a Device that
     *                        hasn't been persisted yet (test fixtures).
     * @param ?int $id        null when freshly emitted by Device::recordEvent();
     *                        set when the repository rehydrates a persisted row.
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
        public ?int $deviceId = null,
        public ?int $id = null,
    ) {
    }

    public function hasMedia(): bool
    {
        return $this->media !== null;
    }
}
