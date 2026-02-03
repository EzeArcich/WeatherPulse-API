<?php

// database/migrations/2026_02_03_000001_create_locations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            $table->string('name');                  // "Buenos Aires"
            $table->string('country');       // "Argentina"
            $table->decimal('lat', 10, 7);
            $table->decimal('lon', 10, 7);

            $table->string('timezone')->nullable();  // "America/Argentina/Buenos_Aires"

            $table->timestamps();

            $table->index(['country', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

