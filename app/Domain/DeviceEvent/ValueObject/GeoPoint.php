<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\ValueObject;

use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidValueObjectException;

final readonly class GeoPoint
{
    private function __construct(
        private float  $latitude,
        private float  $longitude,
        private ?float $speed,
        private ?int   $heading,
    ) {
    }

    public static function create(float $latitude, float $longitude, ?float $speed = null, ?int $heading = null): self
    {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidValueObjectException("Latitude out of range: {$latitude}");
        }
        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidValueObjectException("Longitude out of range: {$longitude}");
        }
        if ($speed !== null && $speed < 0) {
            throw new InvalidValueObjectException("Speed must be non-negative: {$speed}");
        }
        if ($heading !== null && ($heading < 0 || $heading > 360)) {
            throw new InvalidValueObjectException("Heading must be 0..360: {$heading}");
        }

        return new self($latitude, $longitude, $speed, $heading);
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function speed(): ?float
    {
        return $this->speed;
    }

    public function heading(): ?int
    {
        return $this->heading;
    }
}
