<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Factory\Howen;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidPayloadException;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;
use DeviceEventIngestionService\Domain\DeviceEvent\Factory\IncomingEventFactoryInterface;
use DeviceEventIngestionService\Domain\DeviceEvent\IncomingEvent;
use DeviceEventIngestionService\Domain\DeviceEvent\Interface\IncomingEventPayloadValidator;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DedupHash;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\DeviceImei;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventTimestamp;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventType;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\GeoPoint;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\Media;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;

final readonly class HowenEventFactory implements IncomingEventFactoryInterface
{
    public const string PROTOCOL = 'HOWEN';

    /** @var array<string, string> */
    private const RULES = [
        'imei'               => 'required|string|max:32',
        'plate'              => 'required|string|max:64',
        'alarmCode'          => 'required|string|max:32',
        'alarmTime'          => 'required|integer',
        'location'           => 'required|array',
        'location.latitude'  => 'required|numeric',
        'location.longitude' => 'required|numeric',
        'location.speedKmh'  => 'nullable|numeric',
        'video'              => 'nullable|array',
        'video.name'         => 'required_with:video|string',
        'video.ch'           => 'nullable|integer',
        'video.len'          => 'nullable|integer',
        'video.format'       => 'nullable|string',
    ];

    public function __construct(private IncomingEventPayloadValidator $validator)
    {
    }

    public function protocol(): string
    {
        return self::PROTOCOL;
    }

    public function create(array $payload): IncomingEvent
    {
        $this->validator->validate($payload, self::RULES, 'Invalid HOWEN payload');

        try {
            return new IncomingEvent(
                self::PROTOCOL,
                DeviceImei::fromString((string) $payload['imei']),
                VehicleId::fromString((string) $payload['plate']),
                EventType::fromString(HowenAlarmCodeMap::toEventType((string) $payload['alarmCode'])),
                EventTimestamp::fromUnix((int) $payload['alarmTime']),
                GeoPoint::create(
                    (float) $payload['location']['latitude'],
                    (float) $payload['location']['longitude'],
                    isset($payload['location']['speedKmh']) ? (float) $payload['location']['speedKmh'] : null,
                ),
                $this->extractMedia($payload),
                DedupHash::fromParts(
                    self::PROTOCOL,
                    (string) $payload['imei'],
                    (string) $payload['alarmTime'],
                    strtoupper((string) $payload['alarmCode']),
                ),
                $payload,
            );
        } catch (InvalidValueObjectException $e) {
            throw new InvalidPayloadException('Invalid HOWEN payload', ['_' => [$e->getMessage()]], $e);
        }
    }

    /** @param array<string, mixed> $payload */
    private function extractMedia(array $payload): ?Media
    {
        $video = $payload['video'] ?? null;
        if (false === is_array($video) || empty($video['name'])) {
            return null;
        }

        return Media::create(
            isset($video['ch']) ? (int) $video['ch'] : null,
            (string) $video['name'],
            isset($video['len']) ? (int) $video['len'] : null,
            isset($video['format']) ? (string) $video['format'] : null,
            'video',
        );
    }
}
