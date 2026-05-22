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

    /**
     * Persist the device's mutable state (currently just last_seen_at).
     * Should be a fast no-op when the aggregate hasn't actually changed.
     */
    public function save(Device $device): void;
}
