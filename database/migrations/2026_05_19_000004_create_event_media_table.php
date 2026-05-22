<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('event_id');

            $table->smallInteger('channel')->nullable();
            $table->string('file_name', 255);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('codec', 32)->nullable();
            $table->string('media_type', 32)->nullable();

            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('device_events')->cascadeOnDelete();
            $table->index('event_id', 'idx_event_media_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_media');
    }
};
