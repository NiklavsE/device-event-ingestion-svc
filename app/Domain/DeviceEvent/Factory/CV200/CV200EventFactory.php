<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Factory\CV200;

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

final readonly class CV200EventFactory implements IncomingEventFactoryInterface
{
    public const string PROTOCOL = 'CV200';

    /** @var array<string, string> */
    private const RULES = [
        'device_imei'             => 'required|string|max:32',
        'vehicle_id'              => 'required|string|max:64',
        'event_id'                => 'required|string|max:128',
        'event_type'              => 'required|string|max:64',
        'timestamp'               => 'required|string',
        'gps'                     => 'required|array',
        'gps.lat'                 => 'required|numeric',
        'gps.lng'                 => 'required|numeric',
        'gps.speed'               => 'nullable|numeric',
        'gps.heading'             => 'nullable|integer',
        'camera'                  => 'nullable|array',
        'camera.file_name'        => 'required_with:camera|string',
        'camera.channel'          => 'nullable|integer',
        'camera.duration_seconds' => 'nullable|integer',
        'camera.codec'            => 'nullable|string',
        'camera.media_type'       => 'nullable|string',
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
        $this->validator->validate($payload, self::RULES, 'Invalid CV200 payload');

        try {
            return new IncomingEvent(
                self::PROTOCOL,
                DeviceImei::fromString((string) $payload['device_imei']),
                VehicleId::fromString((string) $payload['vehicle_id']),
                EventType::fromString((string) $payload['event_type']),
                EventTimestamp::fromIso8601((string) $payload['timestamp']),
                GeoPoint::create(
                    (float) $payload['gps']['lat'],
                    (float) $payload['gps']['lng'],
                    isset($payload['gps']['speed']) ? (float) $payload['gps']['speed'] : null,
                    isset($payload['gps']['heading']) ? (int) $payload['gps']['heading'] : null,
                ),
                $this->extractMedia($payload),
                DedupHash::fromParts(
                    self::PROTOCOL,
                    (string) $payload['device_imei'],
                    (string) $payload['event_id'],
                ),
                $payload,
            );
        } catch (InvalidValueObjectException $e) {
            throw new InvalidPayloadException('Invalid CV200 payload', ['_' => [$e->getMessage()]], $e);
        }
    }

    /** @param array<string, mixed> $payload */
    private function extractMedia(array $payload): ?Media
    {
        $camera = $payload['camera'] ?? null;
        if (false === is_array($camera) || empty($camera['file_name'])) {
            return null;
        }

        return Media::create(
            isset($camera['channel']) ? (int) $camera['channel'] : null,
            (string) $camera['file_name'],
            isset($camera['duration_seconds']) ? (int) $camera['duration_seconds'] : null,
            isset($camera['codec']) ? (string) $camera['codec'] : null,
            isset($camera['media_type']) ? (string) $camera['media_type'] : null,
        );
    }
}
