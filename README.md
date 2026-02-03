# WeatherPulse API (Laravel + Open-Meteo)

A clean, production-style Laravel API that integrates with **[Open-Meteo](https://open-meteo.com/)** (no API key required) to:

- Fetch **current weather by city name** (on-demand, cached)
- Manage **saved locations**
- Run a **scheduled sync** (queue jobs) to persist **weather snapshots** for history/analytics
- Expose endpoints ready for a frontend/dashboard

---

## Why this project exists

This repo is a portfolio-grade example of:

- External API integration (HTTP client + mapping)
- Clean layering (Application / Infrastructure / Domain)
- Caching to reduce external calls
- Background jobs + scheduling
- Idempotent persistence (no duplicated snapshots)
- Easy local setup with a Postman collection

---

## Features

- **City search → weather now**: `GET /api/weather/search?city=...`
- **Locations CRUD**: create/list/delete saved locations
- **Async syncing**: `POST /api/sync` dispatches a job that syncs all saved locations
- **Scheduler-ready**: hourly sync can run via cron or `php artisan schedule:work`
- **Snapshot history**: retrieve latest and historical weather snapshots

---

## Tech Stack

- Laravel 12
- HTTP client via `Illuminate\Support\Facades\Http`
- Queue jobs (`database`, `redis`)
- Cache (`file`, `database`, `redis`)
- External provider: **[Open-Meteo](https://open-meteo.com/)** (Geocoding + Forecast)

---

## Architecture (high level)

### Infrastructure
- `OpenMeteoGeocodingClient` / `OpenMeteoForecastClient` → HTTP calls
- `OpenMeteoMapper` → normalizes provider payloads into DTOs
- `OpenMeteo*Provider` → implements contracts used by the app

### Application
- `WeatherSearchService` → orchestrates geocode → forecast → map → cache
- `WeatherSyncService` → syncs saved locations & persists snapshots

### Domain
- DTOs (`CityLocationDTO`, `WeatherReadingDTO`, `WeatherReportDTO`)
- Contracts (`GeocodingProvider`, `ForecastProvider`)

This keeps the app stable even if you swap providers later.

---

## API Endpoints

### 1) Weather search (no DB required)

**GET** `/api/weather/search?city=Buenos Aires`

Returns normalized current weather for a city name (cached).

---

### 2) Locations

**GET** `/api/locations`  
List saved locations.

**POST** `/api/locations`  
Create/save a location (geocoding resolves lat/lon automatically).

Request body:
```json
{ "name": "Buenos Aires" }
DELETE /api/locations/{id}
Remove a saved location.

3) Snapshots (persisted)
GET /api/locations/{id}/latest
Returns the latest persisted snapshot for a location.

GET /api/locations/{id}/snapshots?from=2026-01-01&to=2026-02-01
Returns snapshot history for a date range.

4) Sync (queue)
POST /api/sync
Dispatches a job to sync all saved locations (and store snapshots).

Setup (Local)
Requirements
PHP 8.2+

Composer

A database (MySQL/Postgres/SQLite)

Optional: Redis (recommended for queue/cache in production)

Install
bash
Copiar código
composer install
cp .env.example .env
php artisan key:generate
Configure DB
Update .env with your DB settings, then run:

bash
Copiar código
php artisan migrate
Run the app
bash
Copiar código
php artisan serve
Queue & Scheduler
Run queue worker
Set your queue driver in .env (e.g. database or redis):

env
Copiar código
QUEUE_CONNECTION=database
If using database, create the queue tables:

bash
Copiar código
php artisan queue:table
php artisan migrate
Start the worker:

bash
Copiar código
php artisan queue:work
Run scheduler (hourly sync)
If you configured an hourly schedule (e.g. SyncAllLocationsJob), you can run:

bash
Copiar código
php artisan schedule:work
In a real server, configure cron:

bash
Copiar código
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
Postman Collection
Import the collection file:

postman/WeatherPulse.postman_collection.json

Import steps
Open Postman → Import

Choose Raw text

Paste the JSON below (or load the file from the repo)

Set the collection variable:

base_url = http://localhost:8000

Postman collection schema:
https://schema.getpostman.com/json/collection/v2.1.0/collection.json

<details> <summary>Postman Collection JSON</summary>
json
Copiar código
{
  "info": {
    "name": "WeatherPulse API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
    "_postman_id": "weatherpulse-collection-001"
  },
  "item": [
    {
      "name": "Search Weather by City",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{base_url}}/api/weather/search?city=Buenos Aires",
          "host": ["{{base_url}}"],
          "path": ["api", "weather", "search"],
          "query": [
            { "key": "city", "value": "Buenos Aires" }
          ]
        }
      }
    },
    {
      "name": "Locations - List",
      "request": {
        "method": "GET",
        "url": {
          "raw": "{{base_url}}/api/locations",
          "host": ["{{base_url}}"],
          "path": ["api", "locations"]
        }
      }
    },
    {
      "name": "Locations - Store",
      "request": {
        "method": "POST",
        "header": [
          { "key": "Content-Type", "value": "application/json" }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"name\": \"Buenos Aires\"\n}"
        },
        "url": {
          "raw": "{{base_url}}/api/locations",
          "host": ["{{base_url}}"],
          "path": ["api", "locations"]
        }
      }
    },
    {
      "name": "Locations - Delete",
      "request": {
        "method": "DELETE",
        "url": {
          "raw": "{{base_url}}/api/locations/1",
          "host": ["{{base_url}}"],
          "path": ["api", "locations", "1"]
        }
      }
    },
    {
      "name": "Location Weather - Latest Snapshot",
      "request": {
        "method": "GET",
        "url": {
          "raw": "{{base_url}}/api/locations/1/latest",
          "host": ["{{base_url}}"],
          "path": ["api", "locations", "1", "latest"]
        }
      }
    },
    {
      "name": "Location Weather - Snapshot History",
      "request": {
        "method": "GET",
        "url": {
          "raw": "{{base_url}}/api/locations/1/snapshots?from=2026-01-01&to=2026-02-01",
          "host": ["{{base_url}}"],
          "path": ["api", "locations", "1", "snapshots"],
          "query": [
            { "key": "from", "value": "2026-01-01" },
            { "key": "to", "value": "2026-02-01" }
          ]
        }
      }
    },
    {
      "name": "Sync All Locations (Dispatch Job)",
      "request": {
        "method": "POST",
        "header": [
          { "key": "Content-Type", "value": "application/json" }
        ],
        "url": {
          "raw": "{{base_url}}/api/sync",
          "host": ["{{base_url}}"],
          "path": ["api", "sync"]
        }
      }
    }
  ],
  "variable": [
    { "key": "base_url", "value": "http://localhost:8000" }
  ]
}
</details>
Notes / Gotchas
If your locations.lat and locations.lon are decimal, Eloquent returns them as strings by default.
Add casts on the model:

php
Copiar código
protected $casts = [
  'lat' => 'float',
  'lon' => 'float',
];
Credits / Attribution
Weather data and geocoding are powered by Open-Meteo.
This project is for educational/portfolio purposes and follows typical API consumption best practices (caching, retries, and graceful error handling).

Roadmap (optional)
Add daily/hourly aggregated metrics endpoints

Add alerts (e.g., “notify me if temp < X”)

Add OpenAPI/Swagger docs

License
MIT (or choose your preferred license)

makefile
Copiar código
::contentReference[oaicite:0]{index=0}