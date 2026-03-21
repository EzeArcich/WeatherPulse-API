<?php

namespace Tests\Feature;

use App\Domain\Weather\Contracts\ForecastProvider;
use App\Domain\Weather\Contracts\GeocodingProvider;
use App\Domain\Weather\DTO\CityLocationDTO;
use App\Domain\Weather\DTO\WeatherReadingDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeatherSearchEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_normalized_weather_data(): void
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

        $this->app->bind(ForecastProvider::class, fn () => new class implements ForecastProvider {
            public function current(float $lat, float $lon, ?string $timezone = null): WeatherReadingDTO
            {
                return new WeatherReadingDTO(
                    observedAt: new \DateTimeImmutable('2026-03-20T10:00:00+00:00'),
                    temperatureC: 28.5,
                    windKph: 12.4,
                    precipMm: 0.0,
                    humidity: 62,
                    raw: ['provider' => 'fake'],
                );
            }
        });

        $response = $this->getJson('/api/weather/search?city=Buenos Aires');

        $response->assertOk()
            ->assertJsonPath('city.name', 'Buenos Aires')
            ->assertJsonPath('city.country', 'Argentina')
            ->assertJsonPath('current.temperature_c', 28.5)
            ->assertJsonPath('current.wind_kph', 12.4)
            ->assertJsonPath('current.humidity', 62)
            ->assertJsonPath('stale', false);
    }

    public function test_search_validates_the_city_query_parameter(): void
    {
        $response = $this->getJson('/api/weather/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['city']);
    }
}
