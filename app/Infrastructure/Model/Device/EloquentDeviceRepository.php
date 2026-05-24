<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Model\Device;

use DeviceEventIngestionService\Domain\Device\Device;
use DeviceEventIngestionService\Domain\Device\Exception\DeviceNotFoundException;
use DeviceEventIngestionService\Domain\Device\Interface\DeviceRepositoryInterface;
use DeviceEventIngestionService\Domain\Device\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\Vehicle\VehicleId;
use Illuminate\Support\Carbon;

final class EloquentDeviceRepository implements DeviceRepositoryInterface
{
    public function ofImei(DeviceImei $imei): Device
    {
        $row = EloquentDeviceModel::query()->where('imei', $imei->value())->first();
        if ($row === null) {
            throw new DeviceNotFoundException($imei);
        }

        return Device::rehydrate(
            DeviceImei::fromString($row->imei),
            VehicleId::fromString($row->vehicle_external_id),
            $row->last_seen_at?->toDateTimeImmutable(),
            id: $row->id,
        );
    }

    public function save(Device $device): void
    {
        $lastSeenAt = $device->lastSeenAt() === null
            ? null
            : Carbon::instance($device->lastSeenAt());

        if ($device->id() === null) {
            // First-time persist (commissioning flow). The aggregate carries no
            // surrogate yet, so we insert and let MySQL assign one.
            EloquentDeviceModel::create([
                'imei'                => $device->imei()->value(),
                'vehicle_external_id' => $device->vehicleId()->value(),
                'last_seen_at'        => $lastSeenAt,
            ]);

            return;
        }

        EloquentDeviceModel::query()
            ->whereKey($device->id())
            ->update([
                'vehicle_external_id' => $device->vehicleId()->value(),
                'last_seen_at'        => $lastSeenAt,
            ]);
    }
}
