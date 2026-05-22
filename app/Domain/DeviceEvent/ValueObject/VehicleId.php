<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;

final class VehicleId
{
    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '' || strlen($trimmed) > 64) {
            throw new InvalidValueObjectException('Vehicle id must be a non-empty string up to 64 chars');
        }

        return new self($trimmed);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
