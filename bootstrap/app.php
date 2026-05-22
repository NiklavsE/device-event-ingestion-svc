<?php

declare(strict_types=1);

use DeviceEventIngestionService\Domain\Device\Exception\DeviceNotFoundException;
use DeviceEventIngestionService\Domain\Device\Exception\VehicleMismatchException;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\InvalidPayloadException;
use DeviceEventIngestionService\Domain\DeviceEvent\Exception\UnsupportedProtocolException;
use DeviceEventIngestionService\Ui\Http\Middleware\ApiKeyAuth;
use DeviceEventIngestionService\Ui\Http\Middleware\AssignRequestId;
use DeviceEventIngestionService\Ui\Http\Middleware\BindRequestContextToLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));

        // Map domain exceptions to HTTP responses globally so controllers
        // stay free of try/catch ladders. Each renderable matches a single
        // domain concern and produces a structured 422 body.

        $exceptions->render(function (UnsupportedProtocolException $e, Request $request) {
            return response()->json([
                'error' => 'unsupported_protocol',
                'message' => $e->getMessage(),
            ], 422);
        });

        $exceptions->render(function (InvalidPayloadException $e, Request $request) {
            return response()->json([
                'error' => 'invalid_payload',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (DeviceNotFoundException $e, Request $request) {
            return response()->json([
                'error' => 'unknown_device',
                'message' => $e->getMessage(),
                'imei' => $e->imei->value(),
            ], 422);
        });

        $exceptions->render(function (VehicleMismatchException $e, Request $request) {
            return response()->json([
                'error' => 'vehicle_mismatch',
                'message' => $e->getMessage(),
                'imei' => $e->imei->value(),
                'installed_vehicle_id' => $e->installedVehicleId->value(),
                'claimed_vehicle_id' => $e->claimedVehicleId->value(),
            ], 422);
        });
    })
    ->create();
