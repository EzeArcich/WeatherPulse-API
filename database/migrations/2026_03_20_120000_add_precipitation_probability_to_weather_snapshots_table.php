<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('weather_snapshots', function (Blueprint $table) {
            $table->unsignedTinyInteger('precipitation_probability')
                ->nullable()
                ->after('wind_kph');
        });
    }

    public function down(): void
    {
        Schema::table('weather_snapshots', function (Blueprint $table) {
            $table->dropColumn('precipitation_probability');
        });
    }
};
