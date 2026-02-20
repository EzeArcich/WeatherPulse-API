#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8000}"

print_test() {
  local title="$1"
  echo
  echo "=== ${title} ==="
}

call() {
  local method="$1"
  local url="$2"
  local data="${3:-}"

  local tmp
  tmp="$(mktemp)"

  local code
  if [[ -n "${data}" ]]; then
    code="$(curl -sS -o "${tmp}" -w "%{http_code}" -X "${method}" \
      -H "Accept: application/json" \
      -H "Content-Type: application/json" \
      -d "${data}" \
      "${url}")"
  else
    code="$(curl -sS -o "${tmp}" -w "%{http_code}" -X "${method}" \
      -H "Accept: application/json" \
      "${url}")"
  fi

  echo "HTTP ${code}"
  cat "${tmp}"
  rm -f "${tmp}"
}

print_test "GET /api/cities"
call GET "${BASE_URL}/api/cities"

print_test "GET /api/weather/search?city=Buenos Aires"
call GET "${BASE_URL}/api/weather/search?city=Buenos%20Aires"

print_test "POST /api/cities (create city)"
create_response="$(mktemp)"
create_code="$(curl -sS -o "${create_response}" -w "%{http_code}" -X POST \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Buenos Aires"}' \
  "${BASE_URL}/api/cities")"
echo "HTTP ${create_code}"
cat "${create_response}"

city_id="$(grep -oE '"id":[0-9]+' "${create_response}" | head -n1 | cut -d: -f2 || true)"
rm -f "${create_response}"

if [[ -z "${city_id}" ]]; then
  echo
  echo "No se pudo detectar city_id automaticamente. Saltando latest/snapshots/delete."
  exit 0
fi

print_test "GET /api/cities/${city_id}/latest"
call GET "${BASE_URL}/api/cities/${city_id}/latest"

print_test "GET /api/cities/${city_id}/snapshots"
call GET "${BASE_URL}/api/cities/${city_id}/snapshots"

print_test "POST /api/sync"
call POST "${BASE_URL}/api/sync"

print_test "DELETE /api/cities/${city_id}"
call DELETE "${BASE_URL}/api/cities/${city_id}"

