<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Controllers\Api\V1;

use DeviceEventIngestionService\Application\Services\ListVehicleEvents\ListVehicleEventsService;
use DeviceEventIngestionService\Ui\Http\Requests\ListVehicleEventsRequest;
use DeviceEventIngestionService\Ui\Http\Resources\DeviceEventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class VehicleEventsController
{
    public function __construct(private ListVehicleEventsService $handler)
    {
    }

    public function __invoke(ListVehicleEventsRequest $request, string $vehicleId): JsonResponse
    {
        $page = $this->handler->execute($request->toQuery($vehicleId));

        $paginator = new LengthAwarePaginator(
            $page->items,
            $page->total,
            $page->perPage,
            $page->page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ],
        );

        return DeviceEventResource::collection($paginator)
            ->additional([
                'meta' => ['vehicle_id' => $vehicleId],
            ])
            ->response();
    }
}
