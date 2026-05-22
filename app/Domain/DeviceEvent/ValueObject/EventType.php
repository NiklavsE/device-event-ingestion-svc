<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;

final class EventType
{
    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '' || 1 !== preg_match('/^[a-z0-9_]{1,64}$/', $normalized)) {
            throw new InvalidValueObjectException("Invalid event type: {$value}");
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
