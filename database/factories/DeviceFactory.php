<?php

declare(strict_types=1);

namespace Database\Factories;

use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use DeviceEventIngestionService\Infrastructure\Model\Vehicle\EloquentVehicleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EloquentDeviceModel>
 */
class DeviceFactory extends Factory
{
    protected $model = EloquentDeviceModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'imei'                => $this->fakeImei(),
            'vehicle_external_id' => fn (): string => VehicleFactory::new()->create()->external_id,
            'firmware'            => null,
            'last_seen_at'        => null,
        ];
    }

    public function forVehicle(EloquentVehicleModel|string $vehicle): static
    {
        $externalId = is_string($vehicle) ? $vehicle : $vehicle->external_id;

        return $this->state(['vehicle_external_id' => $externalId]);
    }

    private function fakeImei(): string
    {
        return (string) $this->faker->unique()->numerify('###############');
    }
}
