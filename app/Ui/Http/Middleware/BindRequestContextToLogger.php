<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attaches request_id + per-payload identifiers (protocol, imei) to every
 * log line emitted while handling the request. Combined with the stderr
 * JSON channel this gives one-line-per-event log entries that can be
 * grouped in any structured log aggregator.
 */
class BindRequestContextToLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = [
            'request_id' => $request->attributes->get(AssignRequestId::ATTRIBUTE),
            'method' => $request->getMethod(),
            'path' => $request->path(),
        ];

        if ($request->isJson()) {
            $body = $request->json()->all();
            if (isset($body['protocol']) && is_string($body['protocol'])) {
                $context['protocol'] = $body['protocol'];
            }
            $imei = $body['device_imei'] ?? $body['imei'] ?? null;
            if (is_string($imei) && $imei !== '') {
                $context['device_imei'] = $imei;
            }
        }

        Log::withContext($context);

        return $next($request);
    }
}
