<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\Device;

use DateTimeImmutable;
use DeviceEventIngestionService\Domain\Device\Exception\VehicleMismatchException;
use DeviceEventIngestionService\Domain\DeviceEvent\DeviceEvent;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;
use DeviceEventIngestionService\Domain\DeviceEvent\IncomingEvent;

final class Device
{
    private function __construct(
        private readonly ?int $id,
        private readonly DeviceImei $imei,
        private readonly VehicleId $vehicleId,
        private ?DateTimeImmutable $lastSeenAt,
    ) {
    }

    public static function commission(DeviceImei $imei, VehicleId $vehicleId): self
    {
        return new self(null, $imei, $vehicleId, null);
    }

    public static function rehydrate(
        DeviceImei $imei,
        VehicleId $vehicleId,
        ?DateTimeImmutable $lastSeenAt,
        ?int $id = null,
    ): self {
        return new self($id, $imei, $vehicleId, $lastSeenAt);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function imei(): DeviceImei
    {
        return $this->imei;
    }

    public function vehicleId(): VehicleId
    {
        return $this->vehicleId;
    }

    public function lastSeenAt(): ?DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    /**
     * @throws VehicleMismatchException
     */
    public function recordEvent(IncomingEvent $event): DeviceEvent
    {
        if (! $event->vehicleId->equals($this->vehicleId)) {
            throw new VehicleMismatchException(
                imei: $this->imei,
                installedVehicleId: $this->vehicleId,
                claimedVehicleId: $event->vehicleId,
            );
        }

        $eventTime = $event->eventTimestamp->toDateTimeImmutable();
        if ($this->lastSeenAt === null || $this->lastSeenAt < $eventTime) {
            $this->lastSeenAt = $eventTime;
        }

        return new DeviceEvent(
            $event->protocol,
            $this->imei,
            $this->vehicleId,
            $event->eventType,
            $event->eventTimestamp,
            $event->location,
            $event->media,
            $event->dedupHash,
            $event->rawPayload,
            deviceId: $this->id,
        );
    }
}
