#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

BASE_URL="${BASE_URL:-http://nexopos.test}"
ENDPOINT_PATH="/api/nexopos/v4/consignment/feed"
START_AT="${START_AT:-2026-03-01T08:00:00-04:00}"
TOKEN="${NS_CONSIGNMENT_FEED_TOKEN:-esp32-feed-test-token-20260331}"

PASS_COUNT=0
FAIL_COUNT=0

require_command() {
    local command_name="$1"

    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Missing required command: $command_name" >&2
        exit 1
    fi
}

read_app_env() {
    if [[ -f .env ]]; then
        awk -F= '/^APP_ENV=/{gsub(/"/, "", $2); print tolower($2); exit}' .env
        return
    fi

    echo "local"
}

pretty_print_body() {
    local body_file="$1"

    if command -v jq >/dev/null 2>&1 && jq . "$body_file" >/dev/null 2>&1; then
        jq . "$body_file"
    else
        cat "$body_file"
    fi
}

set_feed_token() {
    echo "Setting NS_CONSIGNMENT_FEED_TOKEN in .env..."
    php artisan env:set NS_CONSIGNMENT_FEED_TOKEN --v="$TOKEN" >/dev/null
}

clear_caches() {
    echo "Clearing Laravel caches..."
    php artisan optimize:clear >/dev/null
}

check_route() {
    echo "Checking route registration..."

    if php artisan route:list | grep -F "consignment/feed" >/dev/null; then
        echo "Route found."
    else
        echo "Route not found in php artisan route:list" >&2
        exit 1
    fi
}

run_case() {
    local label="$1"
    local expected_status="$2"
    local token_mode="$3"
    local url="$4"
    local body_file
    local http_code
    local curl_args

    body_file="$(mktemp)"
    curl_args=(
        -sS
        -o "$body_file"
        -w "%{http_code}"
        -H "Accept: application/json"
    )

    case "$token_mode" in
        valid)
            curl_args+=( -H "Authorization: Bearer $TOKEN" )
            ;;
        wrong)
            curl_args+=( -H "Authorization: Bearer wrong-token" )
            ;;
        none)
            ;;
        *)
            echo "Unknown token mode: $token_mode" >&2
            rm -f "$body_file"
            exit 1
            ;;
    esac

    echo
    echo "== $label =="
    echo "URL: $url"

    if ! http_code="$(curl "${curl_args[@]}" "$url")"; then
        echo "curl request failed." >&2
        rm -f "$body_file"
        FAIL_COUNT=$((FAIL_COUNT + 1))
        return
    fi

    echo "HTTP $http_code"
    pretty_print_body "$body_file"

    if [[ "$http_code" == "$expected_status" ]]; then
        echo "Result: PASS"
        PASS_COUNT=$((PASS_COUNT + 1))
    else
        echo "Result: FAIL (expected $expected_status)"
        FAIL_COUNT=$((FAIL_COUNT + 1))
    fi

    rm -f "$body_file"
}

main() {
    local app_env
    local valid_expected_status
    local feed_url

    require_command php
    require_command curl

    feed_url="${BASE_URL%/}${ENDPOINT_PATH}"

    set_feed_token
    clear_caches
    check_route

    app_env="$(read_app_env)"

    if [[ "$app_env" == "local" || "$app_env" == "testing" ]]; then
        valid_expected_status="200"
    else
        valid_expected_status="403"
    fi

    echo
    echo "Base URL: $BASE_URL"
    echo "APP_ENV: ${app_env:-unknown}"
    echo "Using token: $TOKEN"
    echo "Using start_at: $START_AT"
    echo "Expected valid request status over HTTP: $valid_expected_status"

    run_case \
        "Valid token + valid start_at" \
        "$valid_expected_status" \
        "valid" \
        "$feed_url?start_at=$START_AT"

    run_case \
        "Missing token" \
        "401" \
        "none" \
        "$feed_url?start_at=$START_AT"

    run_case \
        "Wrong token" \
        "401" \
        "wrong" \
        "$feed_url?start_at=$START_AT"

    run_case \
        "Missing start_at" \
        "422" \
        "valid" \
        "$feed_url"

    run_case \
        "Malformed start_at" \
        "422" \
        "valid" \
        "$feed_url?start_at=not-a-date"

    echo
    echo "Passed: $PASS_COUNT"
    echo "Failed: $FAIL_COUNT"

    if [[ "$FAIL_COUNT" -gt 0 ]]; then
        exit 1
    fi
}

main "$@"
