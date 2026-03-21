<?php

namespace Tests\Feature;

use App\Domain\Weather\Contracts\ForecastProvider;
use App\Domain\Weather\Contracts\GeocodingProvider;
use App\Domain\Weather\DTO\CityLocationDTO;
use App\Domain\Weather\DTO\ForecastResultDTO;
use App\Domain\Weather\DTO\HourlyWeatherReadingDTO;
use App\Domain\Weather\DTO\WeatherReadingDTO;
use Illuminate\Support\Facades\Cache;
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
                    precipitationProbability: 35,
                    humidity: 62,
                    raw: ['provider' => 'fake'],
                );
            }

            public function report(float $lat, float $lon, ?string $timezone = null): ForecastResultDTO
            {
                return new ForecastResultDTO(
                    current: $this->current($lat, $lon, $timezone),
                    hourly: [
                        new HourlyWeatherReadingDTO(
                            time: new \DateTimeImmutable('2026-03-20T10:00:00+00:00'),
                            temperatureC: 28.5,
                            windKph: 12.4,
                            precipMm: 0.0,
                            precipitationProbability: 35,
                            humidity: 62,
                        ),
                        new HourlyWeatherReadingDTO(
                            time: new \DateTimeImmutable('2026-03-20T11:00:00+00:00'),
                            temperatureC: 27.8,
                            windKph: 14.1,
                            precipMm: 0.8,
                            precipitationProbability: 55,
                            humidity: 68,
                        ),
                    ],
                );
            }
        });

        $response = $this->getJson('/api/weather/search?city=Buenos Aires');

        $response->assertOk()
            ->assertJsonPath('city.name', 'Buenos Aires')
            ->assertJsonPath('city.country', 'Argentina')
            ->assertJsonPath('current.temperature_c', 28.5)
            ->assertJsonPath('current.wind_kph', 12.4)
            ->assertJsonPath('current.precipitation_probability', 35)
            ->assertJsonPath('hourly.0.temperature_c', 28.5)
            ->assertJsonPath('hourly.0.wind_kph', 12.4)
            ->assertJsonPath('hourly.0.precip_mm', 0)
            ->assertJsonPath('hourly.0.precipitation_probability', 35)
            ->assertJsonPath('hourly.0.humidity', 62)
            ->assertJsonPath('hourly.1.precipitation_probability', 55)
            ->assertJsonPath('current.humidity', 62)
            ->assertJsonPath('stale', false);
    }

    public function test_search_response_is_served_from_cache_with_precipitation_probability(): void
    {
        Cache::flush();

        $calls = new class {
            public int $count = 0;
        };

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

        $this->app->bind(ForecastProvider::class, fn () => new class($calls) implements ForecastProvider {
            public function __construct(private object $calls) {}

            public function current(float $lat, float $lon, ?string $timezone = null): WeatherReadingDTO
            {
                $this->calls->count++;

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
                    hourly: [
                        new HourlyWeatherReadingDTO(
                            time: new \DateTimeImmutable('2026-03-20T10:00:00+00:00'),
                            temperatureC: 28.5,
                            windKph: 12.4,
                            precipMm: 0.0,
                            precipitationProbability: 35,
                            humidity: 62,
                        ),
                    ],
                );
            }
        });

        $this->getJson('/api/weather/search?city=Buenos Aires')
            ->assertOk()
            ->assertJsonPath('current.precipitation_probability', 35)
            ->assertJsonPath('hourly.0.temperature_c', 28.5)
            ->assertJsonPath('hourly.0.precipitation_probability', 35);

        $this->getJson('/api/weather/search?city=Buenos Aires')
            ->assertOk()
            ->assertJsonPath('current.precipitation_probability', 35)
            ->assertJsonPath('hourly.0.temperature_c', 28.5)
            ->assertJsonPath('hourly.0.precipitation_probability', 35);

        $this->assertSame(1, $calls->count);
    }

    public function test_search_validates_the_city_query_parameter(): void
    {
        $response = $this->getJson('/api/weather/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['city']);
    }
}
