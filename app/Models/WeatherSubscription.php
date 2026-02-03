<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherSubscription extends Model
{
    protected $fillable = [
        'location_id','email','is_active','rules','frequency','last_notified_at',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'rules' => 'array',
        'last_notified_at' => 'datetime',
    ];
}

