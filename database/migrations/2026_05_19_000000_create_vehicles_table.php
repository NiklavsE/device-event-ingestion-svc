<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            // external_id (plate / fleet code) is the natural key; the
            // vehicle aggregate is owned by an external fleet-management
            // service, so a local surrogate would just be dead weight.
            $table->string('external_id', 64)->primary();
            $table->string('label', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
