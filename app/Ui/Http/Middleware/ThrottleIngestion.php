<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Ui\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleIngestion
{
    private const int DECAY_SECONDS = 60;

    public function __construct(private readonly RateLimiter $limiter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $maxAttempts = (int) config('ingestion.rate_limit.per_minute', 120);
        $key         = $this->bucketKey($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->rejectWithTooManyRequests($key, $maxAttempts);
        }

        $this->limiter->hit($key, self::DECAY_SECONDS);

        $response  = $next($request);
        $remaining = max(0, $maxAttempts - $this->limiter->attempts($key));

        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);

        return $response;
    }

    private function bucketKey(Request $request): string
    {
        $body = $request->isJson() ? $request->json()->all() : [];
        $imei = $body['device_imei'] ?? $body['imei'] ?? null;

        if (is_string($imei) && $imei !== '') {
            return 'ingestion:imei:' . $imei;
        }

        $apiKey = $request->header('X-Api-Key');
        if (is_string($apiKey) && $apiKey !== '') {
            return 'ingestion:key:' . sha1($apiKey);
        }

        return 'ingestion:ip:' . ($request->ip() ?? 'unknown');
    }

    private function rejectWithTooManyRequests(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json(
            ['error' => 'rate_limited'],
            429,
            [
                'Retry-After'           => (string) $retryAfter,
                'X-RateLimit-Limit'     => (string) $maxAttempts,
                'X-RateLimit-Remaining' => '0',
            ],
        );
    }
}
