<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event\Factory\CV200;

use DeviceEventIngestionService\Domain\DeviceEvent\Factory\CV200\CV200EventFactory;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidPayloadException;
use DeviceEventIngestionService\Infrastructure\Validation\LaravelIncomingEventPayloadValidator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use PHPUnit\Framework\TestCase;

class CV200EventFactoryTest extends TestCase
{
    private CV200EventFactory $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new CV200EventFactory(
            new LaravelIncomingEventPayloadValidator(
                new ValidationFactory(new Translator(new ArrayLoader(), 'en')),
            ),
        );
    }

    public function testNormalizesFullPayload(): void
    {
        $event = $this->normalizer->create($this->validPayload());

        self::assertSame('CV200', $event->protocol);
        self::assertSame('863725041234567', $event->deviceImei->value());
        self::assertSame('LV-1234', $event->vehicleId->value());
        self::assertSame('harsh_braking', $event->eventType->value());
        self::assertSame('2026-05-12T10:15:30Z', $event->eventTimestamp->toIso8601());
        self::assertSame(56.9496, $event->location->latitude());
        self::assertSame(24.1052, $event->location->longitude());
        self::assertSame(74.0, $event->location->speed());
        self::assertSame(182, $event->location->heading());
        self::assertNotNull($event->media);
        self::assertSame('20260512_101530_CH2.mp4', $event->media->fileName());
        self::assertSame('h264', $event->media->codec());
    }

    public function testDedupHashIsStableForSameEventId(): void
    {
        $payload = $this->validPayload();

        $h1 = $this->normalizer->create($payload)->dedupHash->value();
        $h2 = $this->normalizer->create($payload)->dedupHash->value();

        self::assertSame($h1, $h2);
    }

    public function testDedupHashDiffersWhenEventIdDiffers(): void
    {
        $a = $this->normalizer->create($this->validPayload(['event_id' => 'evt_A']))->dedupHash->value();
        $b = $this->normalizer->create($this->validPayload(['event_id' => 'evt_B']))->dedupHash->value();

        self::assertNotSame($a, $b);
    }

    public function testThrowsWithStructuredErrorsWhenRequiredFieldsMissing(): void
    {
        $payload = $this->validPayload();
        unset($payload['device_imei'], $payload['gps']['lat']);

        try {
            $this->normalizer->create($payload);
            self::fail('Expected InvalidPayloadException');
        } catch (InvalidPayloadException $e) {
            $errors = $e->errors();
            self::assertArrayHasKey('device_imei', $errors);
            self::assertArrayHasKey('gps.lat', $errors);
        }
    }

    public function testMediaIsNullWhenNoFileName(): void
    {
        $payload = $this->validPayload();
        unset($payload['camera']);

        $event = $this->normalizer->create($payload);

        self::assertNull($event->media);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'protocol' => 'CV200',
            'device_imei' => '863725041234567',
            'vehicle_id' => 'LV-1234',
            'event_id' => 'evt_20260512_00001',
            'event_type' => 'harsh_braking',
            'timestamp' => '2026-05-12T10:15:30Z',
            'gps' => [
                'lat' => 56.9496,
                'lng' => 24.1052,
                'speed' => 74,
                'heading' => 182,
            ],
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
