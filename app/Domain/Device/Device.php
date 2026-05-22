<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\Device;

use DateTimeImmutable;
use DeviceEventIngestionService\Domain\Device\Exception\VehicleMismatchException;
use DeviceEventIngestionService\Domain\DeviceEvent\DeviceEvent;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;
use DeviceEventIngestionService\Domain\DeviceEvent\IncomingEvent;

/**
 * Device aggregate root.
 *
 * Owns the device→vehicle installation binding and the device's last-seen
 * heartbeat. Acts as the gatekeeper for every event the device emits — the
 * application service hands an IncomingEvent in, the Device asserts its
 * invariants (vehicle match) and emits an authoritative DeviceEvent the
 * EventRepository can persist without further domain logic.
 */
final class Device
{
    private function __construct(
        private readonly DeviceImei $imei,
        private readonly VehicleId $vehicleId,
        private ?DateTimeImmutable $lastSeenAt,
    ) {
    }

    /**
     * Commission a new device. Called from the (out-of-band) onboarding
     * flow, not from event ingestion.
     */
    public static function commission(DeviceImei $imei, VehicleId $vehicleId): self
    {
        return new self($imei, $vehicleId, null);
    }

    /**
     * Rehydrate from persistence. Only the DeviceRepository should call this.
     */
    public static function rehydrate(
        DeviceImei $imei,
        VehicleId $vehicleId,
        ?DateTimeImmutable $lastSeenAt,
    ): self {
        return new self($imei, $vehicleId, $lastSeenAt);
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
        );
    }
}
