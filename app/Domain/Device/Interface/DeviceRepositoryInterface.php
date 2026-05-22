<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\Device\Interface;

use DeviceEventIngestionService\Domain\Device\Device;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;

interface DeviceRepositoryInterface
{
    /**
     * @thorws DeviceNotFoundException
     */
    public function ofImei(DeviceImei $imei): Device;

    public function save(Device $device): void;
}
