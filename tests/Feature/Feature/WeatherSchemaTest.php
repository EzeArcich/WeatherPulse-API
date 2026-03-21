<?php

namespace Tests\Feature;

use App\Application\Services\WeatherSyncService;
use App\Domain\Weather\Contracts\ForecastProvider;
use App\Domain\Weather\DTO\ForecastResultDTO;
use App\Domain\Weather\DTO\WeatherReadingDTO;
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
            'country' => 'Argentina',
            'lat' => -34.6036844,
            'lon' => -58.3815591,
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);

        $this->assertDatabaseHas('locations', [
            'id' => $loc->id,
            'country' => 'Argentina',
        ]);
    }

    public function test_can_store_weather_snapshot_for_location(): void
    {
        $loc = Location::create([
            'name' => 'Buenos Aires',
            'country' => 'Argentina',
            'lat' => -34.6036844,
            'lon' => -58.3815591,
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);

        $snap = WeatherSnapshot::create([
            'location_id' => $loc->id,
            'temp_c' => 28.50,
            'feels_like_c' => 30.10,
            'humidity' => 62,
            'wind_kph' => 12.4,
            'precipitation_probability' => 35,
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
            'precipitation_probability' => 35,
        ]);
    }

    public function test_prevents_duplicate_snapshot_same_provider_location_and_observed_at(): void
    {
        $loc = Location::create([
            'name' => 'Buenos Aires',
            'country' => 'Argentina',
            'lat' => -34.6036844,
            'lon' => -58.3815591,
            'timezone' => 'America/Argentina/Buenos_Aires',
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

    public function test_sync_persists_precipitation_probability_and_remains_idempotent(): void
    {
        $loc = Location::create([
            'name' => 'Buenos Aires',
            'country' => 'Argentina',
            'lat' => -34.6036844,
            'lon' => -58.3815591,
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);

        $this->app->bind(ForecastProvider::class, fn () => new class implements ForecastProvider {
            public function current(float $lat, float $lon, ?string $timezone = null): WeatherReadingDTO
            {
                return new WeatherReadingDTO(
                    observedAt: new \DateTimeImmutable('2026-03-20T10:00:00+00:00'),
                    temperatureC: 28.5,
                    windKph: 12.4,
                    precipMm: 0.0,
                    precipitationProbability: 35,
                    humidity: 62,
                    raw: ['provider' => 'fake'],
                );
            }

            public function report(float $lat, float $lon, ?string $timezone = null): ForecastResultDTO
            {
                return new ForecastResultDTO(
                    current: $this->current($lat, $lon, $timezone),
                    hourly: [],
                );
            }
        });

        $service = $this->app->make(WeatherSyncService::class);
        $service->syncCity($loc);
        $service->syncCity($loc);

        $this->assertDatabaseHas('weather_snapshots', [
            'location_id' => $loc->id,
            'provider' => 'open-meteo',
            'precipitation_probability' => 35,
        ]);

        $this->assertSame(1, WeatherSnapshot::query()->count());
    }
}
