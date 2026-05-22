<?php

declare(strict_types=1);

namespace Tests;

use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use DeviceEventIngestionService\Infrastructure\Model\Vehicle\EloquentVehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * The default IMEI / vehicle id used across the fixture payloads. Tests
     * that exercise the not-found paths should NOT call givenDevice() and
     * instead post payloads using a different identifier.
     */
    protected const DEFAULT_IMEI = '863725041234567';
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

    /**
     * Register a vehicle. Mirrors the out-of-band onboarding flow that
     * precedes any device commissioning.
     */
    protected function givenVehicle(
        string $externalId = self::DEFAULT_VEHICLE_ID,
        ?string $label = null,
    ): EloquentVehicleModel {
        return EloquentVehicleModel::query()->updateOrCreate(
            ['external_id' => $externalId],
            ['label' => $label],
        );
    }

    /**
     * Commission a device for the given IMEI installed on the given vehicle.
     * The vehicle is auto-registered if missing — the FK between the two is
     * enforced at the application layer, not the DB level, so tests that
     * exercise the device path don't need to spell out the vehicle each time.
     * Tests that want to exercise the "unregistered vehicle" path should
     * skip this helper.
     */
    protected function givenDevice(
        string $imei = self::DEFAULT_IMEI,
        string $vehicleExternalId = self::DEFAULT_VEHICLE_ID,
    ): EloquentDeviceModel {
        $this->givenVehicle($vehicleExternalId);

        return EloquentDeviceModel::query()->firstOrCreate(
            ['imei' => $imei],
            ['vehicle_external_id' => $vehicleExternalId],
        );
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
            'Accept' => 'application/json',
        ], $headers))->postJson('/api/v1/device-events', $payload);
    }
}
