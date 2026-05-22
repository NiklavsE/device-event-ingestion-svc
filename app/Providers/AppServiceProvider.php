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
            $apiKey = $request->header('X-Api-Key');
            $key    = is_string($apiKey) && $apiKey !== ''
                ? 'key:' . sha1($apiKey)
                : 'ip:' . $request->ip();

            return [Limit::perMinute($perMinute)->by($key)];
        });
    }
}
