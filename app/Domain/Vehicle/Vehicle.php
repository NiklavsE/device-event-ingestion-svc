<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\Vehicle;

final readonly class Vehicle
{
    public function __construct(
        private VehicleId $externalId,
        private ?string $label,
    ) {
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
