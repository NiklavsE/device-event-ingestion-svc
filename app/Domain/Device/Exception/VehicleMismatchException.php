<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\Device\Exception;

use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;
use RuntimeException;

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
