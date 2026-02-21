# Shop

Production-oriented ecommerce monolith built with Laravel 12 + Vue 3.

## Tech stack

- PHP 8.4
- Laravel 12 (API + monolith web shell)
- Vue 3 + Vite + TypeScript + Pinia + Vue Router
- MySQL 8.4
- Redis 7

## Quick start

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Application is available at `http://127.0.0.1:8000`.

Set admin bootstrap credentials in `.env` before seed:

```bash
SHOP_ADMIN_EMAIL=admin@shop.local
SHOP_ADMIN_PASSWORD=change-this-password
```

## Useful commands

```bash
php artisan test
composer run lint
composer run analyse
npm run lint
npm run test
npm run type-check
npm run build
php artisan queue:work
php artisan app:healthcheck
php artisan app:performance-smoke
php artisan app:webhook-flow-smoke
php artisan app:api-contract-smoke
php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples
```

## API scope

- `/api/v1/auth/*`
- `/api/v1/catalog/*`
- `/api/v1/cart/*`
- `/api/v1/checkout/*`
- `/api/v1/orders/me`
- `/api/v1/admin/*`
- `/api/v1/webhooks/*`

## Environments

- `.env.example` for local
- `.env.stage.example` for stage
- `.env.prod.example` for production

## Deployment

Use script:

```bash
./deploy/deploy.sh
```

It executes install/build, safe migration sequence, cache warmup, queue restart and smoke checks.

## Operational cache safety

After route/controller changes, clear optimized caches before smoke checks:

```bash
php artisan optimize:clear
php artisan route:list --path=api/v1/admin/promotions
```

## Observability baseline

Structured telemetry is emitted into logs for:

- API request latency (`observability.api_request`)
- catalog cache hit/miss samples (`observability.catalog_cache`)
- webhook processing and lag (`observability.webhook`)
- rolling snapshot report (`php artisan app:observability-report --minutes=60`)
- optional SLO checks with non-zero exit code for CI (`--max-api-slow-rate`, `--max-webhook-lag-warn-rate`)
- required samples guards (`--require-api-samples`, `--require-webhook-samples`)

Configuration:

- `OBSERVABILITY_ENABLED`
- `OBSERVABILITY_CHANNEL`
- `OBSERVABILITY_API_SLOW_MS`
- `OBSERVABILITY_CATALOG_SLOW_MS`
- `OBSERVABILITY_WEBHOOK_SLOW_MS`
- `OBSERVABILITY_WEBHOOK_LAG_WARN_MS`
- `LOG_OBSERVABILITY_PATH`
- `LOG_OBSERVABILITY_LEVEL`

## CI quality gate

Workflow: `.github/workflows/ci.yml` (`Quality Gate`).

It runs a full blocking pipeline:

- `composer run lint`
- `composer run analyse`
- `php artisan test`
- `npm run lint`
- `npm run type-check`
- `npm run test`
- `npm run build`
- production smoke: `php artisan migrate --force`, `php artisan optimize:clear`, `php artisan route:list --path=api/v1/admin/promotions`, `php artisan app:healthcheck`, `php artisan app:performance-smoke`, `php artisan app:webhook-flow-smoke`, `php artisan app:api-contract-smoke`, `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`

To enforce blocking merges, configure branch protection for `main` and require status check:

- `Quality Gate / Full Quality Gate`

## Engineering rules

- Project contribution rules: `AGENTS.md`
- Cursor/agent rules: `.cursorrules`
- Architecture refactor roadmap: `docs/ARCHITECTURE_REFACTOR_PLAN.md`
