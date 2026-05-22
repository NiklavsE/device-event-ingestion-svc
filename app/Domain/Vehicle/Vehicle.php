<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\Vehicle;

use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;

/**
 * Vehicle aggregate.
 *
 * The minimal registry concept: identified by `externalId` (the plate /
 * fleet code), carries an optional human-friendly `label`. Vehicles are
 * onboarded out-of-band, before any Device is commissioned against them.
 *
 * Cross-aggregate referencing is by VehicleId only — Device holds a
 * VehicleId, never a Vehicle. The application layer enforces "vehicle
 * exists before device commissioning" by looking up the Vehicle via
 * VehicleRepositoryInterface::ofExternalId().
 */
final readonly class Vehicle
{
    private function __construct(
        private VehicleId $externalId,
        private ?string $label,
    ) {
    }

    /**
     * Register a new vehicle. Called from the (out-of-band) onboarding flow,
     * not from event ingestion.
     */
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
