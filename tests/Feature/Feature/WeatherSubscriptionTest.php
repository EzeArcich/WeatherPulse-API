<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\WeatherSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeatherSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_can_subscribe_once_per_location(): void
    {
        $loc = Location::create([
            'name' => 'Buenos Aires',
            'country_code' => 'AR',
            'lat' => -34.6036844,
            'lon' => -58.3815591,
            'timezone' => 'America/Argentina/Buenos_Aires',
            'slug' => 'buenos-aires-ar',
        ]);

        WeatherSubscription::create([
            'location_id' => $loc->id,
            'email' => 'test@example.com',
            'rules' => ['temp_c' => ['gte' => 35]],
            'frequency' => 'daily',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        WeatherSubscription::create([
            'location_id' => $loc->id,
            'email' => 'test@example.com',
        ]);
    }
}
