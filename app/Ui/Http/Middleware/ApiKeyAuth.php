<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    private const ANONYMOUS_ENVIRONMENTS = ['local', 'testing'];

    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('ingestion.api_key', '');
        if ($configured === '') {
            return $this->handleMissingConfiguration($next, $request);
        }

        $presented = $request->header('X-Api-Key')
            ?? $this->extractBearer($request->header('Authorization'));

        if (false === is_string($presented) || false === hash_equals($configured, $presented)) {
            return response()->json([
                'error'   => 'unauthorized',
                'message' => 'Missing or invalid API key.',
            ], 401);
        }

        return $next($request);
    }

    private function handleMissingConfiguration(Closure $next, Request $request): Response
    {
        $env = (string) config('app.env', 'production');
        if (in_array($env, self::ANONYMOUS_ENVIRONMENTS, true)) {
            return $next($request);
        }

        Log::critical('ingestion.api_key.missing', [
            'message' => 'INGESTION_API_KEY is not configured; refusing ingestion requests.',
            'env'     => $env,
            'path'    => $request->path(),
        ]);

        return response()->json([
            'error'   => 'service_misconfigured',
            'message' => 'Ingestion endpoint is not properly configured.',
        ], 500);
    }

    private function extractBearer(?string $header): ?string
    {
        if (false === is_string($header)) {
            return null;
        }
        if (1 !== preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return null;
        }

        return trim($m[1]);
    }
}
