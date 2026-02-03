<?php

// database/migrations/2026_02_03_000003_create_weather_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weather_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('location_id')->constrained()->cascadeOnDelete();

            $table->string('email');
            $table->boolean('is_active')->default(true);

            // Reglas de alerta (mínimo flexible)
            // ej: {"temp_c":{"gte":35},"wind_kph":{"gte":60}}
            $table->json('rules')->nullable();

            // Frecuencia para no spamear
            $table->string('frequency')->default('daily'); // daily|hourly|custom
            $table->timestamp('last_notified_at')->nullable();

            $table->timestamps();

            $table->index(['email', 'is_active']);
            $table->unique(['email', 'location_id']); // 1 subs por ciudad por email
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_subscriptions');
    }
};
