<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Event\Factory\Howen;

use DeviceEventIngestionService\Domain\DeviceEvent\Factory\Howen\HowenEventFactory;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidPayloadException;
use DeviceEventIngestionService\Infrastructure\Validation\LaravelIncomingEventPayloadValidator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use PHPUnit\Framework\TestCase;

class HowenEventFactoryTest extends TestCase
{
    private HowenEventFactory $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new HowenEventFactory(
            new LaravelIncomingEventPayloadValidator(
                new ValidationFactory(new Translator(new ArrayLoader(), 'en')),
            ),
        );
    }

    public function testMapsAlarmcodeToCanonicalEventType(): void
    {
        $event = $this->normalizer->create($this->validPayload());

        self::assertSame('HOWEN', $event->protocol);
        self::assertSame('harsh_braking', $event->eventType->value());
        self::assertSame('863725041234567', $event->deviceImei->value());
        self::assertSame('LV-1234', $event->vehicleId->value());
    }

    public function testUnixTimestampIsConvertedToIso(): void
    {
        $event = $this->normalizer->create($this->validPayload(['alarmTime' => 1778580930]));

        self::assertSame('2026-05-12T10:15:30Z', $event->eventTimestamp->toIso8601());
    }

    public function testDedupHashUsesImeiAlarmtimeAlarmcode(): void
    {
        $a = $this->normalizer->create($this->validPayload())->dedupHash->value();
        $b = $this->normalizer->create($this->validPayload(['alarmTime' => 1778580931]))->dedupHash->value();
        $c = $this->normalizer->create($this->validPayload(['alarmCode' => 'HA']))->dedupHash->value();

        self::assertNotSame($a, $b, 'different alarmTime must produce different hashes');
        self::assertNotSame($a, $c, 'different alarmCode must produce different hashes');
    }

    public function testUnknownAlarmCodesFallThroughWithPrefix(): void
    {
        $event = $this->normalizer->create($this->validPayload(['alarmCode' => 'XYZ']));

        self::assertSame('howen_xyz', $event->eventType->value());
    }

    public function testThrowsWhenRequiredFieldsMissing(): void
    {
        $payload = $this->validPayload();
        unset($payload['imei'], $payload['location']);

        try {
            $this->normalizer->create($payload);
            self::fail('Expected InvalidPayloadException');
        } catch (InvalidPayloadException $e) {
            $errors = $e->errors();
            self::assertArrayHasKey('imei', $errors);
        }
    }

    public function testVideoSectionBecomesMediaEntry(): void
    {
        $event = $this->normalizer->create($this->validPayload());

        self::assertNotNull($event->media);
        self::assertSame(2, $event->media->channel());
        self::assertSame('20260512_101530_CH2.mp4', $event->media->fileName());
        self::assertSame('h264', $event->media->codec());
        self::assertSame('video', $event->media->mediaType());
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'protocol'  => 'HOWEN',
            'imei'      => '863725041234567',
            'plate'     => 'LV-1234',
            'alarmCode' => 'HB',
            'alarmTime' => 1778580930,
            'location'  => [
                'latitude'  => 56.9496,
                'longitude' => 24.1052,
                'speedKmh'  => 74,
            ],
            'video'     => [
                'ch'     => 2,
                'name'   => '20260512_101530_CH2.mp4',
                'len'    => 18,
                'format' => 'h264',
            ],
        ], $overrides);
    }
}
