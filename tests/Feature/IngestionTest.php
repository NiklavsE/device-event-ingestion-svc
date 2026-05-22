<?php

declare(strict_types=1);

namespace Tests\Feature;

use DeviceEventIngestionService\Ui\Queue\IngestDeviceEventJob;
use Illuminate\Support\Facades\Queue;
use Tests\FeatureTestCase;
use Tests\PayloadFixtures;

class IngestionTest extends FeatureTestCase
{
    use PayloadFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function testCv200PayloadIsAcknowledgedAndDispatched(): void
    {
        $payload = $this->cv200Payload();

        $this->postEvent($payload)->assertNoContent(202);

        Queue::assertPushed(
            IngestDeviceEventJob::class,
            fn (IngestDeviceEventJob $job): bool => $job->protocol === 'CV200'
                && $job->payload['device_imei'] === '863725041234567'
                && $job->payload['event_id'] === 'evt_20260512_00001',
        );
    }

    public function testHowenPayloadIsAcknowledgedAndDispatched(): void
    {
        $this->postEvent($this->howenPayload())->assertNoContent(202);

        Queue::assertPushed(
            IngestDeviceEventJob::class,
            fn (IngestDeviceEventJob $job): bool => $job->protocol === 'HOWEN'
                && $job->payload['imei'] === '863725041234567',
        );
    }

    public function testAcknowledgesEvenWhenWorkerWouldRejectPayload(): void
    {
        // No device commissioned for this IMEI — worker would raise
        // DeviceNotFoundException, but the HTTP request never sees that.
        $this->postEvent($this->cv200Payload())->assertNoContent(202);

        Queue::assertPushed(IngestDeviceEventJob::class);
    }

    public function testRejectsPayloadMissingProtocol(): void
    {
        $payload = $this->cv200Payload();
        unset($payload['protocol']);

        $this->postEvent($payload)->assertStatus(422)
            ->assertJsonValidationErrors('protocol');

        Queue::assertNothingPushed();
    }

    public function testRejectsUnknownProtocol(): void
    {
        $payload             = $this->cv200Payload();
        $payload['protocol'] = 'NOPE';

        $this->postEvent($payload)->assertStatus(422)
            ->assertJsonValidationErrors('protocol');

        Queue::assertNothingPushed();
    }

    public function testRejectsRequestWithoutApiKey(): void
    {
        $this->postJson('/api/v1/device-events', $this->cv200Payload())
            ->assertStatus(401);

        Queue::assertNothingPushed();
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

        Queue::assertNothingPushed();
    }

    public function testMissingKeyConfigurationBypassesAuthInLocalAndTestingEnvironments(): void
    {
        config()->set('ingestion.api_key', '');
        config()->set('app.env', 'local');

        $this->postJson(
            '/api/v1/device-events',
            $this->cv200Payload(),
            ['Accept' => 'application/json'],
        )->assertStatus(202);

        Queue::assertPushed(IngestDeviceEventJob::class);
    }
}
