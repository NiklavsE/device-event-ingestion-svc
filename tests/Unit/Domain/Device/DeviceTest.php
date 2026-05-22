<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Device;

use DateTimeImmutable;
use DeviceEventIngestionService\Domain\Device\Device;
use DeviceEventIngestionService\Domain\Device\Exception\VehicleMismatchException;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DedupHash;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventTimestamp;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventType;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\GeoPoint;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;
use DeviceEventIngestionService\Domain\DeviceEvent\IncomingEvent;
use PHPUnit\Framework\TestCase;

class DeviceTest extends TestCase
{
    public function testRecordEventEmitsDeviceEventWithAuthoritativeVehicleId(): void
    {
        $device = Device::commission(
            DeviceImei::fromString('863725041234567'),
            VehicleId::fromString('LV-1234'),
        );

        $event = $device->recordEvent($this->normalizedEventFor('LV-1234'));

        self::assertSame('LV-1234', $event->vehicleId->value());
        self::assertSame('863725041234567', $event->deviceImei->value());
        self::assertSame('harsh_braking', $event->eventType->value());
    }

    public function testRecordEventBumpsLastSeenAtForwardOnly(): void
    {
        $device = Device::rehydrate(
            DeviceImei::fromString('863725041234567'),
            VehicleId::fromString('LV-1234'),
            new DateTimeImmutable('2026-05-12T12:00:00Z'),
        );

        // Earlier event must not pull last_seen_at backwards.
        $device->recordEvent($this->normalizedEventFor('LV-1234', '2026-05-12T10:00:00Z'));
        $lastSeen = $device->lastSeenAt();
        self::assertNotNull($lastSeen);
        self::assertSame('2026-05-12T12:00:00+00:00', $lastSeen->format('c'));

        // Later event advances the heartbeat.
        $device->recordEvent($this->normalizedEventFor('LV-1234', '2026-05-12T14:00:00Z'));
        $lastSeen = $device->lastSeenAt();
        self::assertNotNull($lastSeen);
        self::assertSame('2026-05-12T14:00:00+00:00', $lastSeen->format('c'));
    }

    public function testRecordEventRejectsMismatchedVehicleClaim(): void
    {
        $device = Device::commission(
            DeviceImei::fromString('863725041234567'),
            VehicleId::fromString('LV-1234'),
        );

        $this->expectException(VehicleMismatchException::class);

        $device->recordEvent($this->normalizedEventFor('LV-9999'));
    }

    private function normalizedEventFor(string $vehicleId, string $timestamp = '2026-05-12T10:15:30Z'): IncomingEvent
    {
        return new IncomingEvent(
            'CV200',
            DeviceImei::fromString('863725041234567'),
            VehicleId::fromString($vehicleId),
            EventType::fromString('harsh_braking'),
            EventTimestamp::fromIso8601($timestamp),
            GeoPoint::create(56.9496, 24.1052),
            null,
            DedupHash::fromParts('CV200', '863725041234567', $timestamp),
            [],
        );
    }
}
