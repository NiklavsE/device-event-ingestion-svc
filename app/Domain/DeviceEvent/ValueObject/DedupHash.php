<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;

/**
 * Stable fingerprint for an event. Two payloads referring to the same
 * device-event must produce the same DedupHash; different events must
 * produce different hashes.
 *
 * Built per protocol — see EventFactoryInterface implementations.
 */
final class DedupHash
{
    private function __construct(private readonly string $value)
    {
    }

    /**
     * Build a hash from a set of identifying parts. Parts are joined
     * with `|` to keep boundaries unambiguous and hashed with SHA-256.
     */
    public static function fromParts(string ...$parts): self
    {
        foreach ($parts as $part) {
            if ($part === '') {
                throw new InvalidValueObjectException('DedupHash parts cannot be empty');
            }
        }

        return new self(hash('sha256', implode('|', $parts)));
    }

    public static function fromHex(string $hex): self
    {
        if (1 !== preg_match('/^[a-f0-9]{64}$/', $hex)) {
            throw new InvalidValueObjectException('DedupHash must be a 64-char hex string');
        }

        return new self($hex);
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
