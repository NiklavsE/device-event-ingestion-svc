<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\Device\Interface;

use DeviceEventIngestionService\Domain\Device\Device;
use DeviceEventIngestionService\Domain\Device\Exception\DeviceNotFoundException;
use DeviceEventIngestionService\Domain\Device\ValueObject\DeviceImei;

interface DeviceRepositoryInterface
{
    /**
     * @throws DeviceNotFoundException
     */
    public function ofImei(DeviceImei $imei): Device;

    public function save(Device $device): void;
}
