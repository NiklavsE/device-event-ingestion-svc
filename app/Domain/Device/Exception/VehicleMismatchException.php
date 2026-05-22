<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\Device\Exception;

use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;
use RuntimeException;

/**
 * Raised when an event's payload claims a vehicle id that doesn't match the
 * device's recorded installation. Either the device was moved without the
 * fleet system being updated, or the upstream producer has a bug.
 */
final class VehicleMismatchException extends RuntimeException
{
    public function __construct(
        public readonly DeviceImei $imei,
        public readonly VehicleId $installedVehicleId,
        public readonly VehicleId $claimedVehicleId,
    ) {
        parent::__construct(sprintf(
            "Device '%s' is installed on vehicle '%s' but the event claims vehicle '%s'.",
            $imei->value(),
            $installedVehicleId->value(),
            $claimedVehicleId->value(),
        ));
    }
}
