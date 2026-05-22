<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final readonly class EventTimestamp
{
    private function __construct(private DateTimeImmutable $value)
    {
    }

    public static function fromIso8601(string $value): self
    {
        try {
            $dt = new DateTimeImmutable($value);
        } catch (Throwable $e) {
            throw new InvalidValueObjectException("Invalid ISO-8601 timestamp: {$value}", 0, $e);
        }

        return new self($dt->setTimezone(new DateTimeZone('UTC')));
    }

    public static function fromUnix(int $seconds): self
    {
        if ($seconds < 0) {
            throw new InvalidValueObjectException("Invalid unix timestamp: {$seconds}");
        }

        return new self((new DateTimeImmutable('@' . $seconds))->setTimezone(new DateTimeZone('UTC')));
    }

    public function toDateTimeImmutable(): DateTimeImmutable
    {
        return $this->value;
    }

    public function toIso8601(): string
    {
        return $this->value->format('Y-m-d\TH:i:s\Z');
    }
}
