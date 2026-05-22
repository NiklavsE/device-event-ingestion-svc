<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;

class VehicleEventsQueryTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Every test in this class queries events for the default vehicle,
        // so the device must be commissioned up front. The unknown-vehicle
        // path is exercised by testReturnsEmptyForUnknownVehicle() using a
        // different vehicle id (no events exist for it).
        $this->givenDevice();
    }

    public function testReturnsEventsForTheVehicleInDescendingOrder(): void
    {
        $this->postEvent($this->cv200(['event_id' => 'evt_001', 'timestamp' => '2026-05-01T10:00:00Z']));
        $this->postEvent($this->cv200(['event_id' => 'evt_002', 'timestamp' => '2026-05-02T10:00:00Z']));
        $this->postEvent($this->cv200(['event_id' => 'evt_003', 'timestamp' => '2026-05-03T10:00:00Z']));

        $response = $this->getEvents('LV-1234');

        $response->assertStatus(200)
            ->assertJsonPath('meta.count', 3)
            ->assertJsonCount(3, 'data');

        $timestamps = array_column($response->json('data'), 'event_timestamp');
        self::assertSame([
            '2026-05-03T10:00:00Z',
            '2026-05-02T10:00:00Z',
            '2026-05-01T10:00:00Z',
        ], $timestamps);
    }

    public function testFiltersByEventType(): void
    {
        $this->postEvent($this->cv200(['event_id' => 'evt_001', 'event_type' => 'harsh_braking']));
        $this->postEvent($this->cv200(['event_id' => 'evt_002', 'event_type' => 'speeding']));

        $response = $this->getEvents('LV-1234', ['event_type' => 'speeding']);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_type', 'speeding');
    }

    public function testFiltersByFromAndTo(): void
    {
        $this->postEvent($this->cv200(['event_id' => 'evt_001', 'timestamp' => '2026-04-30T10:00:00Z']));
        $this->postEvent($this->cv200(['event_id' => 'evt_002', 'timestamp' => '2026-05-02T10:00:00Z']));
        $this->postEvent($this->cv200(['event_id' => 'evt_003', 'timestamp' => '2026-06-01T10:00:00Z']));

        $response = $this->getEvents('LV-1234', [
            'from' => '2026-05-01',
            'to' => '2026-05-31',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_timestamp', '2026-05-02T10:00:00Z');
    }

    public function testFiltersByHasMedia(): void
    {
        $this->postEvent($this->cv200(['event_id' => 'evt_001']));

        $noMedia = $this->cv200(['event_id' => 'evt_002']);
        unset($noMedia['camera']);
        $this->postEvent($noMedia);

        $this->getEvents('LV-1234', ['has_media' => '1'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.media.file_name', '20260512_101530_CH2.mp4');

        $this->getEvents('LV-1234', ['has_media' => '0'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.media', null);
    }

    public function testRespectsLimitWithHardCap(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->postEvent($this->cv200([
                'event_id' => "evt_{$i}",
                'timestamp' => sprintf('2026-05-%02dT10:00:00Z', $i),
            ]));
        }

        $this->getEvents('LV-1234', ['limit' => 2])
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function testReturnsEmptyForUnknownVehicle(): void
    {
        $this->getEvents('UNKNOWN')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function testRejectsInvalidDate(): void
    {
        $this->getEvents('LV-1234', ['from' => 'not-a-date'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('from');
    }

    /**
     * @param array<string, mixed> $query
     * @return \Illuminate\Testing\TestResponse<\Illuminate\Http\Response>
     */
    private function getEvents(string $vehicleId, array $query = []): \Illuminate\Testing\TestResponse
    {
        $url = "/api/v1/vehicles/{$vehicleId}/events";
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $this->withHeader('X-Api-Key', 'test-api-key')->getJson($url);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function cv200(array $overrides = []): array
    {
        return array_replace_recursive([
            'protocol' => 'CV200',
            'device_imei' => '863725041234567',
            'vehicle_id' => 'LV-1234',
            'event_id' => 'evt_default',
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
        ], $overrides);
    }
}
