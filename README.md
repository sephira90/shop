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
npm run lint:ox
npm run format:ox:check
npm run test
npm run type-check
npm run build
php artisan queue:work
php artisan app:healthcheck
php artisan app:performance-smoke
php artisan app:webhook-flow-smoke
php artisan app:api-contract-smoke
php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples
php artisan app:observability-alert-check
php artisan app:oncall-drill-smoke
php artisan app:maintenance-cleanup --dry-run
php artisan app:maintenance-cleanup
```

Targeted smoke execution:
```bash
php artisan app:api-contract-smoke --only=shipping_webhook
php artisan app:performance-smoke --only=admin_orders_summary
php artisan app:webhook-flow-smoke --persist
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
- scheduled alert check wrapper (`php artisan app:observability-alert-check`) for routing failures to email/slack/pagerduty
- scheduled on-call drill command (`php artisan app:oncall-drill-smoke`)

Configuration:

- `OBSERVABILITY_ENABLED`
- `OBSERVABILITY_CHANNEL`
- `OBSERVABILITY_API_SLOW_MS`
- `OBSERVABILITY_CATALOG_SLOW_MS`
- `OBSERVABILITY_WEBHOOK_SLOW_MS`
- `OBSERVABILITY_WEBHOOK_LAG_WARN_MS`
- `APP_OBSERVABILITY_ALERTS_ENABLED`
- `APP_OBSERVABILITY_ALERTS_CRON`
- `APP_OBSERVABILITY_ALERTS_WINDOW_MINUTES`
- `APP_OBSERVABILITY_ALERTS_MAX_API_SLOW_RATE`
- `APP_OBSERVABILITY_ALERTS_MAX_WEBHOOK_LAG_WARN_RATE`
- `APP_OBSERVABILITY_ALERTS_REQUIRE_API_SAMPLES`
- `APP_OBSERVABILITY_ALERTS_REQUIRE_WEBHOOK_SAMPLES`
- `APP_OBSERVABILITY_ALERTS_COOLDOWN_MINUTES`
- `APP_OBSERVABILITY_ALERTS_EMAIL_ENABLED`
- `APP_OBSERVABILITY_ALERTS_EMAIL_RECIPIENTS`
- `APP_OBSERVABILITY_ALERTS_SLACK_ENABLED`
- `APP_OBSERVABILITY_ALERTS_SLACK_WEBHOOK_URL`
- `APP_OBSERVABILITY_ALERTS_PAGERDUTY_ENABLED`
- `APP_OBSERVABILITY_ALERTS_PAGERDUTY_INTEGRATION_KEY`
- `APP_OBSERVABILITY_ALERTS_PAGERDUTY_SEVERITY`
- `APP_ONCALL_DRILL_ENABLED`
- `APP_ONCALL_DRILL_CRON`
- `APP_ONCALL_DRILL_WITH_WRITE_SMOKES`
- `APP_ONCALL_DRILL_PERSIST`
- `LOG_OBSERVABILITY_PATH`
- `LOG_OBSERVABILITY_LEVEL`

## CI quality gate

Workflow: `.github/workflows/ci.yml` (`Quality Gate`).

It runs a full blocking pipeline:

- backend alias: `composer run quality:backend`
- expands to: `composer run lint`, `composer run analyse`, `php artisan test`
- frontend alias: `composer run quality:frontend`
- expands to: `npm run lint`, `npm run lint:ox`, `npm run format:ox:check`, `npm run type-check`, `npm run test`, `npm run build`
- support aliases:
  - `composer run ops:clear`
  - `composer run ops:routes-smoke`
  - `composer run ops:observability-report`
- CI production smoke alias: `composer run ops:ci-production-smoke`
- production smoke: `php artisan migrate --force`, `composer run ops:ci-production-smoke`
- targeted smoke examples:
  - `php artisan app:api-contract-smoke --only=shipping_webhook`
  - `php artisan app:performance-smoke --only=admin_orders_summary`
  - `php artisan app:webhook-flow-smoke --persist`

Deployment smoke alias:

- `composer run ops:production-smoke-core`

To enforce blocking merges, configure branch protection for `main` and require status check:

- `Quality Gate / Full Quality Gate`

## Engineering rules

- Project contribution rules: `AGENTS.md`
- Cursor/agent rules: `.cursorrules`
- Architecture refactor roadmap (active): `docs/ARCHITECTURE_REFACTOR_NEXT.md`
- Historical architecture roadmap (archived): `docs/ARCHITECTURE_REFACTOR_PLAN.md`
- Execution plan: `docs/REFACTORING_EXECUTION_PLAN.md`
- Ops runbook (checkout/webhooks): `docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md`
