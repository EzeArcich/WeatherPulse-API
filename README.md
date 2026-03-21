WeatherPulse API (Laravel + Open-Meteo)

A clean, production-style Laravel API that integrates with Open-Meteo (no API key required):
https://open-meteo.com/

It allows you to:
- Fetch current weather by city name (on-demand, cached)
- Manage saved cities
- Run a scheduled sync (queue jobs) to persist weather snapshots for history/analytics
- Expose endpoints ready for a frontend/dashboard
- Expose precipitation probability when Open-Meteo provides it for the matching hourly slot
- Expose a normalized hourly forecast block in the weather search response


WHY THIS PROJECT EXISTS

This repo is a portfolio-grade example of:
- External API integration (HTTP client + mapping)
- Clean layering (Application / Infrastructure / Domain)
- Caching to reduce external calls
- Background jobs + scheduling
- Idempotent persistence (no duplicated snapshots)
- Easy local setup and quick endpoint testing


FEATURES

- City search → current weather + hourly forecast: GET /api/weather/search?city=...
- Cities management: list/create/delete saved cities
- Async syncing: POST /api/sync dispatches a job that syncs all saved cities
- Scheduler enabled: an hourly scheduled job dispatches city syncs
- Snapshot history: retrieve latest and historical weather snapshots
- Normalized weather search fields include `precipitation_probability` when available
- Persisted snapshots include `precipitation_probability` for new synced records


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
- WeatherSyncService: syncs saved cities & persists snapshots

Domain
- DTOs: CityLocationDTO, WeatherReadingDTO, HourlyWeatherReadingDTO, WeatherReportDTO, ForecastResultDTO
- Contracts: GeocodingProvider, ForecastProvider


API ENDPOINTS

1) Weather search
- GET /api/weather/search?city=Buenos Aires
  Returns normalized current weather plus hourly forecast for a city name (cached).
  `current` includes `temperature_c`, `wind_kph`, `precip_mm`, `precipitation_probability`, and `humidity`.
  `hourly` includes `time`, `temperature_c`, `wind_kph`, `precip_mm`, `precipitation_probability`, and `humidity`.

2) Cities
- GET /api/cities
  List saved cities.

- POST /api/cities
  Create/save a city (geocoding resolves lat/lon automatically).
  Request body example:
  { "name": "Buenos Aires" }

- DELETE /api/cities/{id}
  Remove a saved city.

3) Snapshots (persisted)
- GET /api/cities/{id}/latest
  Returns the latest persisted snapshot for a city, including `precipitation_probability`.
  Snapshot payload is serialized from the database model, so it uses persisted field names such as `temp_c`, `wind_kph`, `humidity`, and `precipitation_probability`.
  If a city has no synced snapshots yet, `data` will be `null`.

- GET /api/cities/{id}/snapshots?from=2026-01-01&to=2026-02-01
  Returns snapshot history for a date range, including `precipitation_probability`.
  These records are historical persisted snapshots, not the live `search` response.

4) Sync (queue)
- POST /api/sync
  Dispatches a job to sync all saved cities and persist snapshots.
  This is the endpoint that populates data later returned by `/api/cities/{id}/latest` and `/api/cities/{id}/snapshots`.


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
- If you are upgrading an existing local database, make sure the migration that adds `weather_snapshots.precipitation_probability` has been applied.

Run the app
- composer run dev
- API default URL: http://127.0.0.1:8000


QUEUE & SCHEDULER

Run queue worker
- Set QUEUE_CONNECTION in .env (database or redis)
- Run migrations if needed:
  - php artisan migrate
- Start worker:
  - php artisan queue:work

Run scheduler (hourly sync)
- php artisan schedule:work
- The app already registers an hourly scheduled job that dispatches `SyncAllLocationsJob`.
- In a real server, configure cron:
  * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1


NOTES / GOTCHAS

If your locations.lat and locations.lon are decimal, Eloquent returns them as strings by default.
Add casts on the model:
protected $casts = [
  'lat' => 'float',
  'lon' => 'float',
];

`/api/weather/search` and `/api/cities/{id}/latest` do not represent the same source:
- `/api/weather/search` is live provider data, normalized and cached.
- `/api/cities/{id}/latest` is the most recent persisted snapshot for a followed city.
- If `latest` does not show the newest probability field yet, run `POST /api/sync` and ensure the queue worker is running so a fresh snapshot is stored.


CREDITS / ATTRIBUTION

Weather data and geocoding are powered by Open-Meteo:
https://open-meteo.com/


RESPONSE EXAMPLES

1) GET /api/weather/search?city=Buenos Aires
```json
{
  "city": {
    "name": "Buenos Aires",
    "country": "Argentina",
    "lat": -34.6036844,
    "lon": -58.3815591,
    "timezone": "America/Argentina/Buenos_Aires"
  },
  "current": {
    "observed_at": "2026-03-20T10:00:00+00:00",
    "temperature_c": 28.5,
    "wind_kph": 12.4,
    "precip_mm": 0,
    "precipitation_probability": 35,
    "humidity": 62
  },
  "hourly": [
    {
      "time": "2026-03-20T10:00:00+00:00",
      "temperature_c": 28.5,
      "wind_kph": 12.4,
      "precip_mm": 0,
      "precipitation_probability": 35,
      "humidity": 62
    },
    {
      "time": "2026-03-20T11:00:00+00:00",
      "temperature_c": 27.8,
      "wind_kph": 14.1,
      "precip_mm": 0.8,
      "precipitation_probability": 55,
      "humidity": 68
    }
  ],
  "stale": false
}
```

2) GET /api/cities/{id}/latest
```json
{
  "city": {
    "id": 1,
    "name": "Buenos Aires",
    "country": "Argentina",
    "lat": -34.6036844,
    "lon": -58.3815591,
    "timezone": "America/Argentina/Buenos_Aires"
  },
  "data": {
    "id": 10,
    "location_id": 1,
    "provider": "open-meteo",
    "observed_at": "2026-03-20T10:00:00.000000Z",
    "temp_c": "28.50",
    "wind_kph": "12.40",
    "precipitation_probability": 35,
    "humidity": 62,
    "raw": {
      "current": {
        "time": "2026-03-20T10:00"
      }
    }
  }
}
```

3) GET /api/cities/{id}/snapshots?from=2026-01-01&to=2026-02-01
```json
{
  "city": {
    "id": 1,
    "name": "Buenos Aires",
    "country": "Argentina",
    "lat": -34.6036844,
    "lon": -58.3815591,
    "timezone": "America/Argentina/Buenos_Aires"
  },
  "data": [
    {
      "id": 10,
      "location_id": 1,
      "provider": "open-meteo",
      "observed_at": "2026-03-20T10:00:00.000000Z",
      "temp_c": "28.50",
      "wind_kph": "12.40",
      "precipitation_probability": 35,
      "humidity": 62,
      "raw": {
        "current": {
          "time": "2026-03-20T10:00"
        }
      }
    }
  ]
}
```
