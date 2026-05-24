#!/usr/bin/env bash
#
# Sample curl invocations against a running instance.
# Assumes the service is listening on http://localhost:8000 and that
# INGESTION_API_KEY is set to "local-dev-key-change-me" (matches .env.example).
#
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8000}"
API_KEY="${INGESTION_API_KEY:-local-dev-key-change-me}"

echo "--- POST CV200 event ---"
curl -sS -X POST "${BASE_URL}/api/v1/device-events" \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: ${API_KEY}" \
  --data @"$(dirname "$0")/cv200.json"
echo

echo "--- POST HOWEN event ---"
curl -sS -X POST "${BASE_URL}/api/v1/device-events" \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: ${API_KEY}" \
  --data @"$(dirname "$0")/howen.json"
echo

echo "--- POST CV200 event again (should be a duplicate, 200 + X-Idempotent-Replay) ---"
curl -sS -i -X POST "${BASE_URL}/api/v1/device-events" \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: ${API_KEY}" \
  --data @"$(dirname "$0")/cv200.json"
echo

echo "--- waiting for queue worker to drain ingest jobs ---"
sleep 2

echo "--- GET events for LV-1234 ---"
curl -sS "${BASE_URL}/api/v1/vehicles/LV-1234/events?event_type=harsh_braking&from=2026-05-01&to=2026-05-31" \
  -H "X-Api-Key: ${API_KEY}"
echo
