<?php

declare(strict_types=1);

namespace DeviceEventIngestionService\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        $perMinute = (int) config('ingestion.rate_limit.per_minute', 120);

        RateLimiter::for('ingestion', function (Request $request) use ($perMinute) {
            $imei = $this->extractImei($request);
            $key = $imei !== null ? "imei:{$imei}" : 'ip:' . $request->ip();

            return [
                Limit::perMinute($perMinute)->by($key),
            ];
        });
    }

    private function extractImei(Request $request): ?string
    {
        if (false === $request->isJson()) {
            return null;
        }

        $payload = $request->json()->all();
        $imei = $payload['device_imei'] ?? $payload['imei'] ?? null;

        return is_string($imei) && $imei !== '' ? $imei : null;
    }
}
