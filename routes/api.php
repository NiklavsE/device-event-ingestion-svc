<?php

declare(strict_types=1);

use DeviceEventIngestionService\Ui\Http\Controllers\Api\V1\DeviceEventIngestionController;
use DeviceEventIngestionService\Ui\Http\Controllers\Api\V1\VehicleEventsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['api.key', 'throttle:ingestion'])
        ->post('/device-events', DeviceEventIngestionController::class)
        ->name('api.v1.device-events.ingest');

    Route::middleware('api.key')
        ->get('/vehicles/{vehicleId}/events', VehicleEventsController::class)
        ->name('api.v1.vehicles.events.index');
});
