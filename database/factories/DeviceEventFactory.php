<?php

declare(strict_types=1);

namespace Database\Factories;

use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use DeviceEventIngestionService\Infrastructure\Model\Event\EloquentDeviceEventMediaModel;
use DeviceEventIngestionService\Infrastructure\Model\Event\EloquentDeviceEventModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EloquentDeviceEventModel>
 */
class DeviceEventFactory extends Factory
{
    private const EVENT_TYPES = ['harsh_braking', 'harsh_acceleration', 'speeding', 'panic'];

    protected $model = EloquentDeviceEventModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'device_id'           => fn (): int => DeviceFactory::new()->create()->id,
            'vehicle_external_id' => fn (array $attrs): string => EloquentDeviceModel::query()
                ->whereKey($attrs['device_id'])
                ->value('vehicle_external_id'),
            'protocol'            => 'CV200',
            'event_type'          => $this->faker->randomElement(self::EVENT_TYPES),
            'event_timestamp'     => $this->faker->dateTimeBetween('-30 days', 'now'),
            'latitude'            => $this->faker->latitude(),
            'longitude'           => $this->faker->longitude(),
            'speed'               => $this->faker->numberBetween(0, 130),
            'heading'             => $this->faker->numberBetween(0, 359),
            'dedup_hash'          => $this->faker->unique()->sha256(),
            'raw_payload'         => ['synthetic' => true],
        ];
    }

    public function forDevice(EloquentDeviceModel $device): static
    {
        return $this->state([
            'device_id'           => $device->id,
            'vehicle_external_id' => $device->vehicle_external_id,
        ]);
    }

    public function at(string|\DateTimeInterface $timestamp): static
    {
        return $this->state(['event_timestamp' => $timestamp]);
    }

    public function ofType(string $type): static
    {
        return $this->state(['event_type' => $type]);
    }

    public function withMedia(string $fileName = 'sample.mp4'): static
    {
        return $this->afterCreating(function (EloquentDeviceEventModel $event) use ($fileName): void {
            EloquentDeviceEventMediaModel::create([
                'event_id'         => $event->id,
                'channel'          => 2,
                'file_name'        => $fileName,
                'duration_seconds' => 18,
                'codec'            => 'h264',
                'media_type'       => 'video',
            ]);
        });
    }
}
