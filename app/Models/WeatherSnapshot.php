<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherSnapshot extends Model
{
    protected $fillable = [
        'location_id',
        'temp_c','feels_like_c','humidity','wind_kph',
        'condition_text','condition_code',
        'provider','observed_at','raw',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
        'raw' => 'array',
    ];
}
