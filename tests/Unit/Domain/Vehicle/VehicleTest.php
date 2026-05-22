<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Vehicle;

use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;
use DeviceEventIngestionService\Domain\Vehicle\Vehicle;
use PHPUnit\Framework\TestCase;

class VehicleTest extends TestCase
{
    public function testRegistersWithExternalIdAndOptionalLabel(): void
    {
        $vehicle = Vehicle::register(VehicleId::fromString('LV-1234'), 'Demo Truck');

        self::assertSame('LV-1234', $vehicle->externalId()->value());
        self::assertSame('Demo Truck', $vehicle->label());
    }

    public function testLabelIsOptional(): void
    {
        $vehicle = Vehicle::register(VehicleId::fromString('LV-1234'));

        self::assertNull($vehicle->label());
    }
}
