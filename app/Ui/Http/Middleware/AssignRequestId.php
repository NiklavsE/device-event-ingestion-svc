<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    public const HEADER = 'X-Request-Id';
    public const ATTRIBUTE = 'request_id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header(self::HEADER);
        if (false === is_string($requestId) || 1 !== preg_match('/^[A-Za-z0-9._-]{1,64}$/', $requestId)) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set(self::ATTRIBUTE, $requestId);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }
}
