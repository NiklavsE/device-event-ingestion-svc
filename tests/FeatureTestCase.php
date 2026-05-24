<?php

declare(strict_types=1);

namespace Tests;

use Database\Factories\DeviceFactory;
use Database\Factories\VehicleFactory;
use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use DeviceEventIngestionService\Infrastructure\Model\Vehicle\EloquentVehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected const DEFAULT_IMEI       = '863725041234567';
    protected const DEFAULT_VEHICLE_ID = 'LV-1234';

    protected function setUp(): void
    {
        parent::setUp();

        $this->forgetDedupCache();
    }

    /**
     * Wipe the array cache the CachingEventRepository uses as its dedup
     * pre-check. Test scenarios that simulate "cache flushed mid-flight"
     * call this between requests to force the next save() to go all the
     * way down to the DB UNIQUE constraint.
     */
    protected function forgetDedupCache(): void
    {
        Cache::flush();
    }

    protected function createVehicle(
        string $externalId = self::DEFAULT_VEHICLE_ID,
        ?string $label = null,
    ): EloquentVehicleModel {
        $existing = EloquentVehicleModel::query()->where('external_id', $externalId)->first();
        if ($existing !== null) {
            return $existing;
        }

        return VehicleFactory::new()
            ->state(['external_id' => $externalId, 'label' => $label])
            ->create();
    }

    protected function createDevice(
        string $imei = self::DEFAULT_IMEI,
        string $vehicleExternalId = self::DEFAULT_VEHICLE_ID,
    ): EloquentDeviceModel {
        $this->createVehicle($vehicleExternalId);

        $existing = EloquentDeviceModel::query()->where('imei', $imei)->first();
        if ($existing !== null) {
            return $existing;
        }

        return DeviceFactory::new()
            ->forVehicle($vehicleExternalId)
            ->state(['imei' => $imei])
            ->create();
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     * @return \Illuminate\Testing\TestResponse<\Illuminate\Http\Response>
     */
    protected function postEvent(array $payload, array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(array_merge([
            'X-Api-Key' => 'test-api-key',
            'Accept'    => 'application/json',
        ], $headers))->postJson('/api/v1/device-events', $payload);
    }
}
