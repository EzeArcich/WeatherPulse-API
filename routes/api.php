<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WeatherSearchController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CityWeatherController;
use App\Http\Controllers\Api\SyncController;

Route::get('/weather/search', [WeatherSearchController::class, 'search']);

Route::apiResource('cities', CityController::class)->only(['index','store','destroy']);

Route::get('/cities/{city}/latest', [CityWeatherController::class, 'latest']);
Route::get('/cities/{city}/snapshots', [CityWeatherController::class, 'snapshots']);

Route::post('/sync', [SyncController::class, 'sync']);
