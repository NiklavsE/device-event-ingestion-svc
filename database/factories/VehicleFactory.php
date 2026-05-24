<?php

declare(strict_types=1);

namespace Database\Factories;

use DeviceEventIngestionService\Infrastructure\Model\Vehicle\EloquentVehicleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EloquentVehicleModel>
 */
class VehicleFactory extends Factory
{
    protected $model = EloquentVehicleModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'external_id' => $this->fakeExternalId(),
            'label'       => $this->faker->optional(0.5)->company(),
        ];
    }

    public function labelled(string $label): static
    {
        return $this->state(['label' => $label]);
    }

    private function fakeExternalId(): string
    {
        return sprintf('LV-%04d', $this->faker->unique()->numberBetween(1, 9999));
    }
}
