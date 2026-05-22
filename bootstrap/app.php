<?php

declare(strict_types=1);

use DeviceEventIngestionService\Ui\Http\Middleware\ApiKeyAuth;
use DeviceEventIngestionService\Ui\Http\Middleware\AssignRequestId;
use DeviceEventIngestionService\Ui\Http\Middleware\BindRequestContextToLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/healthz',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            AssignRequestId::class,
            BindRequestContextToLogger::class,
        ]);

        $middleware->alias([
            'api.key' => ApiKeyAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // No HTTP-time domain exception mapping. The controller validates
        // the top-level FormRequest, dispatches IngestDeviceEventJob, and
        // returns 202 unconditionally — every domain exception
        // (DeviceNotFoundException, VehicleMismatchException,
        // InvalidPayloadException, ...) fires inside the queue worker,
        // where Laravel's queue machinery records the failure in
        // `failed_jobs` after retries are exhausted. The empty callback
        // is still required so the framework binds its default
        // ExceptionHandler during bootstrap.
    })
    ->create();
