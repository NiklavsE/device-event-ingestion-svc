<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('imei', 32)->unique();
            // The vehicle this device is installed on. Captured at
            // commissioning (out-of-band) and treated as authoritative
            // when recording events — payloads claiming a different
            // vehicle are rejected with VehicleMismatchException.
            $table->string('vehicle_external_id', 64);
            $table->string('firmware', 64)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('vehicle_external_id', 'idx_devices_vehicle');
            $table->foreign('vehicle_external_id')
                ->references('external_id')->on('vehicles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
