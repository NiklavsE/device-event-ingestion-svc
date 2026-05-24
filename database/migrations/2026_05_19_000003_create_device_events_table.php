<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_events', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('device_id');
            // Vehicle is stored denormalized as an external id. The value is asserted by the Device aggregate at write
            // time, so there's no need for an FK join at persist or read time.
            $table->string('vehicle_external_id', 64);

            $table->string('protocol', 32);
            $table->string('event_type', 64);
            $table->timestamp('event_timestamp');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 6, 2)->nullable();
            $table->smallInteger('heading')->nullable();
            // Authoritative idempotency key.
            $table->char('dedup_hash', 64)->unique();
            $table->json('raw_payload');
            $table->timestamps();
            $table->foreign('device_id')->references('id')->on('devices')->restrictOnDelete();
            $table->foreign('vehicle_external_id')
                ->references('external_id')->on('vehicles')
                ->restrictOnDelete();

            $table->index(['vehicle_external_id', 'event_timestamp'], 'idx_device_events_vehicle_time');
            $table->index(['device_id', 'event_timestamp'], 'idx_device_events_device_time');
            $table->index(['event_type', 'event_timestamp'], 'idx_device_events_type_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_events');
    }
};
