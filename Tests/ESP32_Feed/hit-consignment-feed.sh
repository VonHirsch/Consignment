#!/usr/bin/env bash

set -euo pipefail

BASE_URL="${BASE_URL:-http://nexopos.test}"
ENDPOINT_PATH="/api/nexopos/v4/consignment/feed"
START_AT="${START_AT:-2026-03-01T08:00:00-04:00}"
TOKEN="${NS_CONSIGNMENT_FEED_TOKEN:-esp32-feed-test-token-20260331}"

url="${BASE_URL%/}${ENDPOINT_PATH}?start_at=${START_AT}"

pretty_print_body() {
    local body="$1"

    if command -v jq >/dev/null 2>&1 && printf '%s' "$body" | jq . >/dev/null 2>&1; then
        printf '%s' "$body" | jq .
    else
        printf '%s\n' "$body"
    fi
}

echo "GET $url"
echo "Authorization: Bearer $TOKEN"
echo

response="$(
    curl -sS \
        -w $'\n__HTTP_CODE__:%{http_code}' \
        -H "Accept: application/json" \
        -H "Authorization: Bearer $TOKEN" \
        "$url"
)"

http_code="${response##*$'\n'__HTTP_CODE__:}"
body="${response%$'\n'__HTTP_CODE__:*}"

echo "HTTP $http_code"
pretty_print_body "$body"
