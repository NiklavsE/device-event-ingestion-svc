<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Controllers\Api\V1;

use DeviceEventIngestionService\Ui\Http\Requests\IngestDeviceEventRequest;
use DeviceEventIngestionService\Ui\Queue\IngestDeviceEventJob;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DeviceEventIngestionController
{
    public function __invoke(IngestDeviceEventRequest $request): Response
    {
        IngestDeviceEventJob::dispatch($request->protocol(), $request->all());

        Log::info('device_event.queued', ['protocol' => $request->protocol()]);

        return response()->noContent(202);
    }
}
