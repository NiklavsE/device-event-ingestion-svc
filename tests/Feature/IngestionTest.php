<?php

declare(strict_types=1);

namespace Tests\Feature;

use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use DeviceEventIngestionService\Infrastructure\Model\Event\EloquentEventModel;
use DeviceEventIngestionService\Infrastructure\Model\Event\EloquentDeviceEventMediaModel;
use Tests\FeatureTestCase;

class IngestionTest extends FeatureTestCase
{
    public function testAcceptsCv200PayloadAndAcknowledgesWithEmptyBody(): void
    {
        $this->givenDevice();

        // QUEUE_CONNECTION=sync in phpunit.xml runs the dispatched job inline,
        // so by the time we get here the row already exists.
        $this->postEvent($this->cv200Payload())->assertNoContent(202);

        self::assertSame(1, EloquentDeviceModel::count(), 'should not create additional Device rows');
        self::assertSame(1, EloquentEventModel::count());
        self::assertSame(1, EloquentDeviceEventMediaModel::count());

        $stored = EloquentEventModel::query()->with('media')->sole();
        self::assertSame('CV200', $stored->protocol);
        self::assertSame('harsh_braking', $stored->event_type);
        self::assertSame('LV-1234', $stored->vehicle_external_id);
        self::assertSame('20260512_101530_CH2.mp4', $stored->media?->file_name);
    }

    public function testAcceptsHowenPayloadAndNormalizesToSameShape(): void
    {
        $this->givenDevice();

        $this->postEvent($this->howenPayload())->assertNoContent(202);

        $stored = EloquentEventModel::query()->sole();
        self::assertSame('HOWEN', $stored->protocol);
        self::assertSame('harsh_braking', $stored->event_type);
        self::assertSame('LV-1234', $stored->vehicle_external_id);
    }

    public function testRejectsEventForUnregisteredDevice(): void
    {
        // No device commissioned for this IMEI.
        $this->postEvent($this->cv200Payload())
            ->assertStatus(422)
            ->assertJsonPath('error', 'unknown_device')
            ->assertJsonPath('imei', '863725041234567');

        self::assertSame(0, EloquentDeviceModel::count(), 'must not auto-create devices');
        self::assertSame(0, EloquentEventModel::count());
    }

    public function testRejectsEventWhenPayloadVehicleDoesNotMatchInstallation(): void
    {
        // Device is installed on LV-1234, but the payload claims LV-9999.
        $this->givenDevice(vehicleExternalId: 'LV-1234');

        $payload = $this->cv200Payload();
        $payload['vehicle_id'] = 'LV-9999';

        $this->postEvent($payload)
            ->assertStatus(422)
            ->assertJsonPath('error', 'vehicle_mismatch')
            ->assertJsonPath('imei', '863725041234567')
            ->assertJsonPath('installed_vehicle_id', 'LV-1234')
            ->assertJsonPath('claimed_vehicle_id', 'LV-9999');

        self::assertSame(0, EloquentEventModel::count());
    }

    public function testRejectsPayloadMissingProtocol(): void
    {
        $payload = $this->cv200Payload();
        unset($payload['protocol']);

        $this->postEvent($payload)->assertStatus(422)
            ->assertJsonValidationErrors('protocol');
    }

    public function testRejectsUnknownProtocol(): void
    {
        $payload = $this->cv200Payload();
        $payload['protocol'] = 'NOPE';

        $this->postEvent($payload)->assertStatus(422)
            ->assertJsonValidationErrors('protocol');
    }

    public function testRejectsCv200MissingRequiredField(): void
    {
        $payload = $this->cv200Payload();
        unset($payload['device_imei']);

        $this->postEvent($payload)
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_payload')
            ->assertJsonStructure(['errors' => ['device_imei']]);
    }

    public function testRejectsHowenMissingAlarmcode(): void
    {
        $payload = $this->howenPayload();
        unset($payload['alarmCode']);

        $this->postEvent($payload)
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid_payload')
            ->assertJsonStructure(['errors' => ['alarmCode']]);
    }

    public function testDuplicateCv200IsHandledIdempotently(): void
    {
        $this->givenDevice();

        // Both calls are 202-ack'd identically — the deduplication signal is
        // not visible to the caller in async mode. End-state is what matters:
        // a single row in the DB after two dispatches.
        $this->postEvent($this->cv200Payload())->assertNoContent(202);
        $this->postEvent($this->cv200Payload())->assertNoContent(202);

        self::assertSame(1, EloquentEventModel::count(), 'duplicate must not create a second row');
    }

    public function testDuplicateDetectedWhenOnlyDbConstraintCatchesIt(): void
    {
        $this->givenDevice();

        // Simulate Redis miss by re-binding a fresh in-memory deduplicator
        // after the first request. The second job will pass the Redis check
        // but the UNIQUE on dedup_hash must catch the duplicate.
        $this->postEvent($this->cv200Payload())->assertNoContent(202);

        $this->forgetDedupCache();

        $this->postEvent($this->cv200Payload())->assertNoContent(202);

        self::assertSame(1, EloquentEventModel::count());
    }

    public function testRejectsRequestWithoutApiKey(): void
    {
        $this->postJson('/api/v1/device-events', $this->cv200Payload())
            ->assertStatus(401);
    }

    public function testMissingKeyConfigurationFailsClosedInProductionEnvironments(): void
    {
        config()->set('ingestion.api_key', '');
        config()->set('app.env', 'production');

        $this->postJson(
            '/api/v1/device-events',
            $this->cv200Payload(),
            ['Accept' => 'application/json'],
        )->assertStatus(500)
            ->assertJsonPath('error', 'service_misconfigured');
    }

    public function testMissingKeyConfigurationBypassesAuthInLocalAndTestingEnvironments(): void
    {
        $this->givenDevice();

        config()->set('ingestion.api_key', '');
        config()->set('app.env', 'local');

        $this->postJson(
            '/api/v1/device-events',
            $this->cv200Payload(),
            ['Accept' => 'application/json'],
        )->assertStatus(202);
    }

    /** @return array<string, mixed> */
    private function cv200Payload(): array
    {
        return [
            'protocol' => 'CV200',
            'device_imei' => '863725041234567',
            'vehicle_id' => 'LV-1234',
            'event_id' => 'evt_20260512_00001',
            'event_type' => 'harsh_braking',
            'timestamp' => '2026-05-12T10:15:30Z',
            'gps' => ['lat' => 56.9496, 'lng' => 24.1052, 'speed' => 74, 'heading' => 182],
            'camera' => [
                'channel' => 2,
                'media_type' => 'video',
                'file_name' => '20260512_101530_CH2.mp4',
                'duration_seconds' => 18,
                'codec' => 'h264',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function howenPayload(): array
    {
        return [
            'protocol' => 'HOWEN',
            'imei' => '863725041234567',
            'plate' => 'LV-1234',
            'alarmCode' => 'HB',
            'alarmTime' => 1778580930,
            'location' => ['latitude' => 56.9496, 'longitude' => 24.1052, 'speedKmh' => 74],
            'video' => ['ch' => 2, 'name' => '20260512_101530_CH2.mp4', 'len' => 18, 'format' => 'h264'],
        ];
    }
}
