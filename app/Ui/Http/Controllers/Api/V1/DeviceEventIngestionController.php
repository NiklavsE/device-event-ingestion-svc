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
        // $request->all() — not validated() — because the FormRequest only
        // gates `protocol`; per-protocol shape validation runs in the worker
        // via the factories. The raw body is forwarded intact for the queue.
        IngestDeviceEventJob::dispatch($request->protocol(), $request->all());

        Log::info('device_event.queued', ['protocol' => $request->protocol()]);

        return response()->noContent(202);
    }
}
