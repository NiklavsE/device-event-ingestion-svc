<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Infrastructure\Model\Device;

use DeviceEventIngestionService\Domain\Device\Device;
use DeviceEventIngestionService\Domain\Device\Exception\DeviceNotFoundException;
use DeviceEventIngestionService\Domain\Device\Interface\DeviceRepositoryInterface;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;
use Illuminate\Support\Carbon;

final class EloquentDeviceRepository implements DeviceRepositoryInterface
{
    public function ofImei(DeviceImei $imei): Device
    {
        $row = EloquentDeviceModel::query()->where('imei', $imei->value())->first();
        if ($row === null) {
            ;
            throw new DeviceNotFoundException($imei);
        }

        return Device::rehydrate(
            DeviceImei::fromString($row->imei),
            VehicleId::fromString($row->vehicle_external_id),
            $row->last_seen_at?->toDateTimeImmutable(),
        );
    }

    public function save(Device $device): void
    {
        $row = EloquentDeviceModel::query()->where('imei', $device->imei()->value())->first();
        if ($row === null) {
            // First-time persist (e.g., from a commissioning flow). Repository
            // doesn't infer whether the caller meant insert vs update; it just
            // writes the current aggregate state.
            $row = new EloquentDeviceModel(['imei' => $device->imei()->value()]);
        }

        $row->vehicle_external_id = $device->vehicleId()->value();
        $row->last_seen_at = $device->lastSeenAt() === null
            ? null
            : Carbon::instance($device->lastSeenAt());

        if ($row->isDirty()) {
            $row->save();
        }
    }
}
