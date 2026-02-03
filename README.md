WeatherPulse API (Laravel + Open-Meteo)

A clean, production-style Laravel API that integrates with Open-Meteo (no API key required):
https://open-meteo.com/

It allows you to:
- Fetch current weather by city name (on-demand, cached)
- Manage saved locations
- Run a scheduled sync (queue jobs) to persist weather snapshots for history/analytics
- Expose endpoints ready for a frontend/dashboard


WHY THIS PROJECT EXISTS

This repo is a portfolio-grade example of:
- External API integration (HTTP client + mapping)
- Clean layering (Application / Infrastructure / Domain)
- Caching to reduce external calls
- Background jobs + scheduling
- Idempotent persistence (no duplicated snapshots)
- Easy local setup with a Postman collection


FEATURES

- City search → weather now: GET /api/weather/search?city=...
- Locations CRUD: create/list/delete saved locations
- Async syncing: POST /api/sync dispatches a job that syncs all saved locations
- Scheduler-ready: hourly sync can run via cron or php artisan schedule:work
- Snapshot history: retrieve latest and historical weather snapshots


TECH STACK

- Laravel 12
- HTTP client via Illuminate\Support\Facades\Http
- Queue jobs: database or redis
- Cache: file, database, or redis
- External provider: Open-Meteo (Geocoding + Forecast)
  https://open-meteo.com/


ARCHITECTURE (HIGH LEVEL)

Infrastructure
- OpenMeteoGeocodingClient / OpenMeteoForecastClient: HTTP calls
- OpenMeteoMapper: normalizes provider payloads into DTOs
- OpenMeteo*Provider: implements contracts used by the app

Application
- WeatherSearchService: orchestrates geocode → forecast → map → cache
- WeatherSyncService: syncs saved locations & persists snapshots

Domain
- DTOs: CityLocationDTO, WeatherReadingDTO, WeatherReportDTO
- Contracts: GeocodingProvider, ForecastProvider


API ENDPOINTS

1) Weather search (no DB required)
- GET /api/weather/search?city=Buenos Aires
  Returns normalized current weather for a city name (cached).

2) Locations
- GET /api/locations
  List saved locations.

- POST /api/locations
  Create/save a location (geocoding resolves lat/lon automatically).
  Request body example:
  { "name": "Buenos Aires" }

- DELETE /api/locations/{id}
  Remove a saved location.

3) Snapshots (persisted)
- GET /api/locations/{id}/latest
  Returns the latest persisted snapshot for a location.

- GET /api/locations/{id}/snapshots?from=2026-01-01&to=2026-02-01
  Returns snapshot history for a date range.

4) Sync (queue)
- POST /api/sync
  Dispatches a job to sync all saved locations (and store snapshots).


SETUP (LOCAL)

Requirements
- PHP 8.2+
- Composer
- A database (MySQL/Postgres/SQLite)
- Optional: Redis (recommended for queue/cache in production)

Install
- composer install
- cp .env.example .env
- php artisan key:generate

Configure DB
- Update .env with your DB settings
- php artisan migrate

Run the app
- php artisan serve


QUEUE & SCHEDULER

Run queue worker
- Set QUEUE_CONNECTION in .env (database or redis)
- If using database:
  - php artisan queue:table
  - php artisan migrate
- Start worker:
  - php artisan queue:work

Run scheduler (hourly sync)
- php artisan schedule:work
- In a real server, configure cron:
  * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1


POSTMAN COLLECTION

Collection file path:
- postman/WeatherPulse.postman_collection.json

Import steps:
1) Open Postman → Import
2) Choose Raw text
3) Paste the JSON from the file above (or load it from the repo)
4) Set the collection variable:
   base_url = http://localhost:8000

Postman collection schema:
https://schema.getpostman.com/json/collection/v2.1.0/collection.json


NOTES / GOTCHAS

If your locations.lat and locations.lon are decimal, Eloquent returns them as strings by default.
Add casts on the model:
protected $casts = [
  'lat' => 'float',
  'lon' => 'float',
];


CREDITS / ATTRIBUTION

Weather data and geocoding are powered by Open-Meteo:
https://open-meteo.com/

Th