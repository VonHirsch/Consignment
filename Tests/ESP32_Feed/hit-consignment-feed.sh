#!/usr/bin/env bash

set -euo pipefail

BASE_URL="${BASE_URL:-http://nexopos.test}"
ENDPOINT_PATH="/api/nexopos/v4/consignment/feed"
START_AT="${START_AT:-2026-03-01T08:00:00-04:00}"
TOKEN="${NS_CONSIGNMENT_FEED_TOKEN:-esp32-feed-test-token-20260331}"

body_file="$(mktemp)"
trap 'rm -f "$body_file"' EXIT

url="${BASE_URL%/}${ENDPOINT_PATH}?start_at=${START_AT}"

echo "GET $url"
echo "Authorization: Bearer $TOKEN"
echo

http_code="$(
    curl -sS \
        -o "$body_file" \
        -w "%{http_code}" \
        -H "Accept: application/json" \
        -H "Authorization: Bearer $TOKEN" \
        "$url"
)"

echo "HTTP $http_code"

if command -v jq >/dev/null 2>&1 && jq . "$body_file" >/dev/null 2>&1; then
    jq . "$body_file"
else
    cat "$body_file"
    echo
fi
