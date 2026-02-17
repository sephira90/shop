#!/usr/bin/env bash
set -euo pipefail

echo "Starting deployment"

git pull --rebase
composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build

php artisan down --render=errors::503 || true
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up

./deploy/smoke.sh

echo "Deployment completed"
