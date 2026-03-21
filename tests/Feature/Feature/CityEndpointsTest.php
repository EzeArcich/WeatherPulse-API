<?php

namespace Tests\Feature;

use App\Domain\Weather\Contracts\GeocodingProvider;
use App\Models\Location;
use App\Models\WeatherSnapshot;
use App\Domain\Weather\DTO\CityLocationDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_returns_city_and_latest_snapshot(): void
    {
        $location = Location::create([
            'name' => 'Buenos Aires',
            'country' => 'Argentina',
            'lat' => -34.6036844,
            'lon' => -58.3815591,
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);

        WeatherSnapshot::create([
            'location_id' => $location->id,
            'temp_c' => 28.5,
            'precipitation_probability' => 20,
            'provider' => 'open-meteo',
            'observed_at' => now()->subHour()->seconds(0),
        ]);

        $latest = WeatherSnapshot::create([
            'location_id' => $location->id,
            'temp_c' => 30.2,
            'precipitation_probability' => 35,
            'provider' => 'open-meteo',
            'observed_at' => now()->seconds(0),
        ]);

        $response = $this->getJson("/api/cities/{$location->id}/latest");

        $response->assertOk()
            ->assertJsonPath('city.id', $location->id)
            ->assertJsonPath('data.id', $latest->id)
            ->assertJsonPath('data.precipitation_probability', 35);
    }

    public function test_snapshots_returns_precipitation_probability_in_history(): void
    {
        $location = Location::create([
            'name' => 'Buenos Aires',
            'country' => 'Argentina',
            'lat' => -34.6036844,
            'lon' => -58.3815591,
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);

        WeatherSnapshot::create([
            'location_id' => $location->id,
            'temp_c' => 28.5,
            'precipitation_probability' => 10,
            'provider' => 'open-meteo',
            'observed_at' => '2026-01-10 10:00:00',
        ]);

        WeatherSnapshot::create([
            'location_id' => $location->id,
            'temp_c' => 30.2,
            'precipitation_probability' => 45,
            'provider' => 'open-meteo',
            'observed_at' => '2026-01-10 11:00:00',
        ]);

        $response = $this->getJson("/api/cities/{$location->id}/snapshots?from=2026-01-10&to=2026-01-10");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.precipitation_probability', 10)
            ->assertJsonPath('data.1.precipitation_probability', 45);
    }

    public function test_destroy_removes_the_city(): void
    {
        $location = Location::create([
            'name' => 'Mendoza',
            'country' => 'Argentina',
            'lat' => -32.8908,
            'lon' => -68.8272,
            'timezone' => 'America/Argentina/Mendoza',
        ]);

        $response = $this->deleteJson("/api/cities/{$location->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }

    public function test_store_creates_the_city_from_geocoding_data(): void
    {
        $this->app->bind(GeocodingProvider::class, fn () => new class implements GeocodingProvider {
            public function resolveCity(string $cityName): ?CityLocationDTO
            {
                return new CityLocationDTO(
                    name: 'Buenos Aires',
                    country: 'Argentina',
                    latitude: -34.6036844,
                    longitude: -58.3815591,
                    timezone: 'America/Argentina/Buenos_Aires',
                );
            }
        });

        $response = $this->postJson('/api/cities', [
            'name' => 'Buenos Aires',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Buenos Aires')
            ->assertJsonPath('data.country', 'Argentina');

        $this->assertDatabaseHas('locations', [
            'name' => 'Buenos Aires',
            'country' => 'Argentina',
        ]);
    }

    public function test_store_validates_the_city_name(): void
    {
        $response = $this->postJson('/api/cities', [
            'name' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
