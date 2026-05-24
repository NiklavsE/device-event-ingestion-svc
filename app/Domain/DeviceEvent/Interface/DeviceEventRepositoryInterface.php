<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Domain\DeviceEvent\Interface;

use DeviceEventIngestionService\Domain\DeviceEvent\DeviceEvent;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\DeviceEventAlreadyExists;
use DeviceEventIngestionService\Domain\DeviceEvent\Queries\EventPage;
use DeviceEventIngestionService\Domain\DeviceEvent\Queries\VehicleEventQuery;

interface DeviceEventRepositoryInterface
{
    /**
     * @throws DeviceEventAlreadyExists
     */
    public function save(DeviceEvent $event): void;

    public function ofVehicleQuery(VehicleEventQuery $criteria): EventPage;
}
