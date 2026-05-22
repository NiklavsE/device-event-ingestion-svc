<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;

final class DeviceImei
{
    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '' || 1 !== preg_match('/^[0-9]{14,17}$/', $trimmed)) {
            throw new InvalidValueObjectException("Invalid IMEI: {$value}");
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
