<?php

namespace Tests\Unit;

use App\Infrastructure\Weather\OpenMeteo\OpenMeteoMapper;
use PHPUnit\Framework\TestCase;

class OpenMeteoMapperTest extends TestCase
{
    public function test_it_maps_precipitation_probability_from_matching_hourly_slot(): void
    {
        $mapper = new OpenMeteoMapper();

        $dto = $mapper->toWeatherReadingDTO([
            'current' => [
                'time' => '2026-03-20T10:00',
                'temperature_2m' => 28.5,
                'wind_speed_10m' => 12.4,
                'precipitation' => 0.0,
                'relative_humidity_2m' => 62,
            ],
            'hourly' => [
                'time' => [
                    '2026-03-20T09:00',
                    '2026-03-20T10:00',
                    '2026-03-20T11:00',
                ],
                'precipitation_probability' => [10, 35, 55],
            ],
        ]);

        $this->assertNotNull($dto);
        $this->assertSame(35, $dto->precipitationProbability);
    }

    public function test_it_returns_null_precipitation_probability_when_hourly_field_is_missing(): void
    {
        $mapper = new OpenMeteoMapper();

        $dto = $mapper->toWeatherReadingDTO([
            'current' => [
                'time' => '2026-03-20T10:00',
                'temperature_2m' => 28.5,
                'wind_speed_10m' => 12.4,
                'precipitation' => 0.0,
                'relative_humidity_2m' => 62,
            ],
        ]);

        $this->assertNotNull($dto);
        $this->assertNull($dto->precipitationProbability);
    }

    public function test_it_maps_hourly_precipitation_probability_series(): void
    {
        $mapper = new OpenMeteoMapper();

        $dto = $mapper->toForecastResultDTO([
            'current' => [
                'time' => '2026-03-20T10:00',
                'temperature_2m' => 28.5,
                'wind_speed_10m' => 12.4,
                'precipitation' => 0.0,
                'relative_humidity_2m' => 62,
            ],
            'hourly' => [
                'time' => [
                    '2026-03-20T10:00',
                    '2026-03-20T11:00',
                ],
                'temperature_2m' => [28.5, 27.8],
                'wind_speed_10m' => [12.4, 14.1],
                'precipitation' => [0.0, 0.8],
                'precipitation_probability' => [35, 55],
                'relative_humidity_2m' => [62, 68],
            ],
        ]);

        $this->assertNotNull($dto);
        $this->assertCount(2, $dto->hourly);
        $this->assertSame(28.5, $dto->hourly[0]->temperatureC);
        $this->assertSame(12.4, $dto->hourly[0]->windKph);
        $this->assertSame(0.0, $dto->hourly[0]->precipMm);
        $this->assertSame(35, $dto->hourly[0]->precipitationProbability);
        $this->assertSame(62, $dto->hourly[0]->humidity);
        $this->assertSame(55, $dto->hourly[1]->precipitationProbability);
    }

    public function test_it_matches_current_probability_even_when_current_time_has_seconds(): void
    {
        $mapper = new OpenMeteoMapper();

        $dto = $mapper->toWeatherReadingDTO([
            'current' => [
                'time' => '2026-03-20T10:37:12',
                'temperature_2m' => 28.5,
                'wind_speed_10m' => 12.4,
                'precipitation' => 0.0,
                'relative_humidity_2m' => 62,
            ],
            'hourly' => [
                'time' => [
                    '2026-03-20T10:00',
                    '2026-03-20T11:00',
                ],
                'precipitation_probability' => [35, 55],
            ],
        ]);

        $this->assertNotNull($dto);
        $this->assertSame(35, $dto->precipitationProbability);
    }
}
