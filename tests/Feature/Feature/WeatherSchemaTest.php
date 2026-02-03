<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\WeatherSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeatherSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_location(): void
    {
        $loc = Location::create([
            'name' => 'Buenos Aires',
            'country_code' => 'AR',
            'lat' => -34.6036844,
            'lon' => -58.3815591,
            'timezone' => 'America/Argentina/Buenos_Aires',
            'slug' => 'buenos-aires-ar',
        ]);

        $this->assertDatabaseHas('locations', [
            'id' => $loc->id,
            'slug' => 'buenos-aires-ar',
        ]);
    }

    public function test_can_store_weather_snapshot_for_location(): void
    {
        $loc = Location::create([
            'name' => 'Buenos Aires',
            'country_code' => 'AR',
            'lat' => -34.6036844,
            'lon' => -58.3815591,
            'timezone' => 'America/Argentina/Buenos_Aires',
            'slug' => 'buenos-aires-ar',
        ]);

        $snap = WeatherSnapshot::create([
            'location_id' => $loc->id,
            'temp_c' => 28.50,
            'feels_like_c' => 30.10,
            'humidity' => 62,
            'wind_kph' => 12.4,
            'condition_text' => 'Partly cloudy',
            'condition_code' => '803',
            'provider' => 'open-meteo',
            'observed_at' => now()->seconds(0),
            'raw' => ['provider_payload' => ['ok' => true]],
        ]);

        $this->assertDatabaseHas('weather_snapshots', [
            'id' => $snap->id,
            'location_id' => $loc->id,
            'provider' => 'open-meteo',
        ]);
    }

    public function test_prevents_duplicate_snapshot_same_provider_location_and_observed_at(): void
    {
        $loc = Location::create([
            'name' => 'Buenos Aires',
            'country_code' => 'AR',
            'lat' => -34.6036844,
            'lon' => -58.3815591,
            'timezone' => 'America/Argentina/Buenos_Aires',
            'slug' => 'buenos-aires-ar',
        ]);

        $observedAt = now()->seconds(0);

        WeatherSnapshot::create([
            'location_id' => $loc->id,
            'provider' => 'open-meteo',
            'observed_at' => $observedAt,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        WeatherSnapshot::create([
            'location_id' => $loc->id,
            'provider' => 'open-meteo',
            'observed_at' => $observedAt,
        ]);
    }
}

