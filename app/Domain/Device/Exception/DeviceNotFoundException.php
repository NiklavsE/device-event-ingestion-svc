<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\Device\Exception;

use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;
use RuntimeException;

final class DeviceNotFoundException extends RuntimeException
{
    public function __construct(public readonly DeviceImei $imei)
    {
        parent::__construct(sprintf("Device with IMEI %s is not registered.", $this->imei->value()));
    }
}
