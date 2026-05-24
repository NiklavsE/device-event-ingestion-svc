<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Factories\DeviceEventFactory;
use DeviceEventIngestionService\Infrastructure\Model\Device\EloquentDeviceModel;
use DeviceEventIngestionService\Infrastructure\Model\Event\EloquentDeviceEventModel;
use Tests\FeatureTestCase;

class VehicleEventsQueryTest extends FeatureTestCase
{
    private EloquentDeviceModel $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->device = $this->createDevice();
    }

    public function testReturnsFullyShapedResponseForASingleEvent(): void
    {
        $event = DeviceEventFactory::new()
            ->forDevice($this->device)
            ->state([
                'protocol'        => 'CV200',
                'event_type'      => 'harsh_braking',
                'event_timestamp' => '2026-05-12T10:15:30Z',
                'latitude'        => 56.9496,
                'longitude'       => 24.1052,
                'speed'           => 74,
                'heading'         => 182,
            ])
            ->withMedia('20260512_101530_CH2.mp4')
            ->create();

        $this->getEvents('LV-1234')
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    [
                        'id'              => $event->id,
                        'protocol'        => 'CV200',
                        'device_imei'     => '863725041234567',
                        'vehicle_id'      => 'LV-1234',
                        'event_type'      => 'harsh_braking',
                        'event_timestamp' => '2026-05-12T10:15:30Z',
                        'latitude'        => 56.9496,
                        'longitude'       => 24.1052,
                        'speed'           => 74.0,
                        'heading'         => 182,
                        'media'           => [
                            'channel'          => 2,
                            'file_name'        => '20260512_101530_CH2.mp4',
                            'duration_seconds' => 18,
                            'codec'            => 'h264',
                            'media_type'       => 'video',
                        ],
                    ],
                ],
                'meta' => [
                    'vehicle_id' => 'LV-1234',
                    'count'      => 1,
                ],
            ]);
    }

    public function testReturnsEventsForTheVehicleInDescendingOrder(): void
    {
        $event1 = DeviceEventFactory::new()->forDevice($this->device)->at('2026-05-01T10:00:00Z')->create();
        $event2 = DeviceEventFactory::new()->forDevice($this->device)->at('2026-05-02T10:00:00Z')->create();
        $event3 = DeviceEventFactory::new()->forDevice($this->device)->at('2026-05-03T10:00:00Z')->create();

        $this->getEvents('LV-1234')
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    $this->expectedEventJson($event3),
                    $this->expectedEventJson($event2),
                    $this->expectedEventJson($event1),
                ],
                'meta' => [
                    'vehicle_id' => 'LV-1234',
                    'count'      => 3,
                ],
            ]);
    }

    public function testFiltersByEventType(): void
    {
        DeviceEventFactory::new()->forDevice($this->device)->ofType('harsh_braking')->create();
        $matching = DeviceEventFactory::new()->forDevice($this->device)->ofType('speeding')->create();

        $this->getEvents('LV-1234', ['event_type' => 'speeding'])
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [$this->expectedEventJson($matching)],
                'meta' => ['vehicle_id' => 'LV-1234', 'count' => 1],
            ]);
    }

    public function testFiltersByFromAndTo(): void
    {
        DeviceEventFactory::new()->forDevice($this->device)->at('2026-04-30T10:00:00Z')->create();
        $inRange = DeviceEventFactory::new()->forDevice($this->device)->at('2026-05-02T10:00:00Z')->create();
        DeviceEventFactory::new()->forDevice($this->device)->at('2026-06-01T10:00:00Z')->create();

        $this->getEvents('LV-1234', ['from' => '2026-05-01', 'to' => '2026-05-31'])
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [$this->expectedEventJson($inRange)],
                'meta' => ['vehicle_id' => 'LV-1234', 'count' => 1],
            ]);
    }

    public function testFiltersByHasMedia(): void
    {
        $withMedia    = DeviceEventFactory::new()
            ->forDevice($this->device)
            ->withMedia('20260512_101530_CH2.mp4')
            ->create();
        $withoutMedia = DeviceEventFactory::new()->forDevice($this->device)->create();

        $this->getEvents('LV-1234', ['has_media' => '1'])
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [$this->expectedEventJson($withMedia)],
                'meta' => ['vehicle_id' => 'LV-1234', 'count' => 1],
            ]);

        $this->getEvents('LV-1234', ['has_media' => '0'])
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [$this->expectedEventJson($withoutMedia)],
                'meta' => ['vehicle_id' => 'LV-1234', 'count' => 1],
            ]);
    }

    public function testRespectsLimitWithHardCap(): void
    {
        $events = DeviceEventFactory::new()
            ->forDevice($this->device)
            ->count(5)
            ->sequence(fn ($s) => ['event_timestamp' => sprintf('2026-05-%02dT10:00:00Z', $s->index + 1)])
            ->create();

        // Latest first, capped at 2 by the limit query param.
        $expectedSlice = [
            $this->expectedEventJson($events[4]),
            $this->expectedEventJson($events[3]),
        ];

        $this->getEvents('LV-1234', ['limit' => 2])
            ->assertStatus(200)
            ->assertExactJson([
                'data' => $expectedSlice,
                'meta' => ['vehicle_id' => 'LV-1234', 'count' => 2],
            ]);
    }

    public function testReturnsEmptyForUnknownVehicle(): void
    {
        $this->getEvents('UNKNOWN')
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [],
                'meta' => ['vehicle_id' => 'UNKNOWN', 'count' => 0],
            ]);
    }

    public function testRejectsInvalidDate(): void
    {
        $this->getEvents('LV-1234', ['from' => 'not-a-date'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('from');
    }

    /**
     * @return array<string, mixed>
     */
    private function expectedEventJson(EloquentDeviceEventModel $event): array
    {
        return [
            'id'              => $event->id,
            'protocol'        => $event->protocol,
            'device_imei'     => $event->device->imei,
            'vehicle_id'      => $event->vehicle_external_id,
            'event_type'      => $event->event_type,
            'event_timestamp' => $event->event_timestamp->format('Y-m-d\TH:i:s\Z'),
            'latitude'        => $event->latitude,
            'longitude'       => $event->longitude,
            'speed'           => $event->speed,
            'heading'         => $event->heading,
            'media'           => $event->media === null ? null : [
                'channel'          => $event->media->channel,
                'file_name'        => $event->media->file_name,
                'duration_seconds' => $event->media->duration_seconds,
                'codec'            => $event->media->codec,
                'media_type'       => $event->media->media_type,
            ],
        ];
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
}
