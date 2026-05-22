<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Controllers\Api\V1;

use DeviceEventIngestionService\Application\Services\ListVehicleEvents\ListVehicleEventsService;
use DeviceEventIngestionService\Ui\Http\Requests\ListVehicleEventsRequest;
use DeviceEventIngestionService\Ui\Http\Resources\DeviceEventResource;
use Illuminate\Http\JsonResponse;

readonly class VehicleEventsController
{
    public function __construct(private ListVehicleEventsService $handler)
    {
    }

    public function __invoke(ListVehicleEventsRequest $request, string $vehicleId): JsonResponse
    {
        $events = $this->handler->execute($request->toQuery($vehicleId));

        return DeviceEventResource::collection($events)
            ->additional([
                'meta' => [
                    'vehicle_id' => $vehicleId,
                    'count'      => count($events),
                ],
            ])
            ->response();
    }
}
