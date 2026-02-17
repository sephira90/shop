#!/usr/bin/env bash
set -euo pipefail

APP_URL=${APP_URL:-http://localhost}

curl -fsS "${APP_URL}/up" >/dev/null
curl -fsS "${APP_URL}/api/v1/catalog/products" >/dev/null

echo "Smoke checks passed"
