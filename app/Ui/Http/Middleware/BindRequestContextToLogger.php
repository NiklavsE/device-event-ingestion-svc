<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class BindRequestContextToLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        // Context::add() — not Log::withContext() — so these fields survive
        // the queue boundary: Laravel serialises Context into the dispatched
        // job payload and rehydrates it in the worker, keeping HTTP and
        // worker telemetry correlated by request_id/protocol/device_imei.
        Context::add([
            'request_id' => $request->attributes->get(AssignRequestId::ATTRIBUTE),
            'method'     => $request->getMethod(),
            'path'       => $request->path(),
        ]);

        if ($request->isJson()) {
            $body = $request->json()->all();
            if (isset($body['protocol']) && is_string($body['protocol'])) {
                Context::add('protocol', $body['protocol']);
            }
            $imei = $body['device_imei'] ?? $body['imei'] ?? null;
            if (is_string($imei) && $imei !== '') {
                Context::add('device_imei', $imei);
            }
        }

        return $next($request);
    }
}
