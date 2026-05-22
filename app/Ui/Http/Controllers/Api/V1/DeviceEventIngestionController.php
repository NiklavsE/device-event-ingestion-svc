<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Controllers\Api\V1;

use DeviceEventIngestionService\Infrastructure\Queue\Jobs\IngestDeviceEventJob;
use DeviceEventIngestionService\Ui\Http\Requests\IngestDeviceEventRequest;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class DeviceEventIngestionController extends Controller
{
    public function __invoke(IngestDeviceEventRequest $request): Response
    {
        IngestDeviceEventJob::dispatch($request->protocol(), $request->all());

        Log::info('device_event.queued', ['protocol' => $request->protocol()]);

        return response()->noContent(202);
    }
}
