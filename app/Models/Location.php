<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name','country','lat','lon','timezone','slug'
    ];

    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
    ];
}
