<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Application\Services\ListVehicleEvents;

use DeviceEventIngestionService\Domain\DeviceEvent\DeviceEvent;
use DeviceEventIngestionService\Domain\DeviceEvent\Interface\DeviceEventRepositoryInterface;
use DeviceEventIngestionService\Domain\DeviceEvent\Queries\VehicleEventQuery;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\EventType;
use DeviceEventIngestionService\Domain\DeviceEvent\ValueObject\VehicleId;

final readonly class ListVehicleEventsService
{
    public function __construct(
        private DeviceEventRepositoryInterface $events,
    ) {
    }

    /** @return array<int, DeviceEvent> */
    public function execute(ListVehicleEventsRequest $request): array
    {
        return $this->events->ofVehicleQuery(
            new VehicleEventQuery(
                VehicleId::fromString($request->vehicleExternalId),
                $request->eventType === null ? null : EventType::fromString($request->eventType),
                $request->from,
                $request->to,
                $request->hasMedia,
                $request->limit,
            )
        );
    }
}
