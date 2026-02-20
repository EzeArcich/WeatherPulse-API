<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\WeatherSnapshot;
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
            'provider' => 'open-meteo',
            'observed_at' => now()->subHour()->seconds(0),
        ]);

        $latest = WeatherSnapshot::create([
            'location_id' => $location->id,
            'temp_c' => 30.2,
            'provider' => 'open-meteo',
            'observed_at' => now()->seconds(0),
        ]);

        $response = $this->getJson("/api/cities/{$location->id}/latest");

        $response->assertOk()
            ->assertJsonPath('city.id', $location->id)
            ->assertJsonPath('data.id', $latest->id);
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
}
