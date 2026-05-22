<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\Vehicle;

use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;

final readonly class Vehicle
{
    private function __construct(
        private VehicleId $externalId,
        private ?string $label,
    ) {
    }

    public static function register(VehicleId $externalId, ?string $label = null): self
    {
        return new self($externalId, $label);
    }


    public function externalId(): VehicleId
    {
        return $this->externalId;
    }

    public function label(): ?string
    {
        return $this->label;
    }
}
