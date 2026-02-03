<?php

// database/migrations/2026_02_03_000002_create_weather_snapshots_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weather_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('location_id')->constrained()->cascadeOnDelete();

            // Normalizado (lo que vas a mostrar/usar en lógica)
            $table->decimal('temp_c', 5, 2)->nullable();
            $table->decimal('feels_like_c', 5, 2)->nullable();
            $table->unsignedTinyInteger('humidity')->nullable(); // 0-100
            $table->decimal('wind_kph', 6, 2)->nullable();
            $table->string('condition_text')->nullable();        // "Partly cloudy"
            $table->string('condition_code')->nullable();        // "803" o el code del provider

            // Metadata
            $table->string('provider')->default('open-meteo');   // o "weatherapi", etc.
            $table->timestamp('observed_at');                    // “momento del clima”, no el de inserción

            // Raw del provider para debug/reprocesado
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->index(['location_id', 'observed_at']);
            $table->unique(['provider', 'location_id', 'observed_at']); // evita duplicados si re-sync
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_snapshots');
    }
};
