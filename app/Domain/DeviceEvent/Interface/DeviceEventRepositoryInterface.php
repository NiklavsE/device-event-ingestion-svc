<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Interface;

use DeviceEventIngestionService\Domain\DeviceEvent\DeviceEvent;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\DeviceEventAlreadyExists;
use DeviceEventIngestionService\Domain\DeviceEvent\Queries\VehicleEventQuery;

interface DeviceEventRepositoryInterface
{
    /**
     * Persist a device event.
     *
     * @throws DeviceEventAlreadyExists when an event with the same dedup
     *         hash is already on record. Implementations MUST surface
     *         duplicates via this exception — the application layer treats
     *         duplicate detection as expected behaviour, not a generic error.
     */
    public function save(DeviceEvent $event): void;

    /**
     * @return array<int, DeviceEvent>
     */
    public function ofVehicleQuery(VehicleEventQuery $criteria): array;
}
