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

## Docker Compose quick start

```bash
cp .env.example .env
composer run ops:docker-bootstrap
```

Application is available at `http://localhost:8080`.

Stop stack:

```bash
composer run ops:docker-down
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

Bearer API tokens have a finite lifetime configured by `SANCTUM_TOKEN_EXPIRATION_MINUTES`
(default: `1440`). New tokens persist an explicit `expires_at`; password reset revokes all
tokens, while logout revokes only the current bearer token. Authenticated and guest-capable
cart/checkout routes revalidate `is_active` via the `active.api.user` middleware; an inactive
bearer is rejected with `401` and all of the user's tokens are revoked on the first API use.

New registration and reset credentials use one shared policy: at least 12 characters with
letters and numbers. Login failures are rate-limited by normalized email plus client IP and
always perform one password-hash verification, including unknown-email attempts.

Auth configuration:
- `AUTH_LOGIN_THROTTLE_MAX_ATTEMPTS` (default: `6`)
- `AUTH_LOGIN_THROTTLE_DECAY_SECONDS` (default: `60`)

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
- auth security audit trail (`auth.audit.login.succeeded`, `auth.audit.login.failed`, `auth.audit.logout`, `auth.audit.token.issued`, `auth.audit.token.revoked` with `token_scope` and `revoke_reason`, `auth.audit.password.reset.requested`, `auth.audit.password.reset.completed`, `auth.audit.email.verified`); identity on failure paths is the `sha256` email hash, never raw email
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

- supply-chain audit gate (fail fast, after dependency install):
  - `composer audit` (PHP advisories, including dev dependencies)
  - `npm audit --omit=dev --audit-level=high` (frontend high/critical advisories, excluding dev dependencies)
- backend alias: `composer run quality:backend`
- expands to: `composer run lint`, `composer run analyse`, `php artisan test`
- static-analysis alias: `composer run analyse`
- expands to: `composer run analyse:phpstan`, `composer run analyse:psalm`
- frontend alias: `composer run quality:frontend`
- expands to: `npm run lint`, `npm run lint:ox`, `npm run format:ox:check`, `npm run type-check`, `npm run test`, `npm run build`
- support aliases:
  - `composer run ops:clear`
  - `composer run ops:routes-smoke`
  - `composer run ops:observability-report`
- CI production smoke alias: `composer run ops:ci-production-smoke`
- production smoke: `php artisan migrate --force`, `composer run ops:ci-production-smoke`
- Docker local stack aliases:
  - `composer run ops:docker-up`
  - `composer run ops:docker-down`
  - `composer run ops:docker-bootstrap`
- targeted smoke examples:
  - `php artisan app:api-contract-smoke --only=shipping_webhook`
  - `php artisan app:performance-smoke --only=admin_orders_summary`
  - `php artisan app:webhook-flow-smoke --persist`

Local audit parity:

- `composer run audit` runs `composer audit` locally.
- `npm run audit` runs `npm audit --omit=dev --audit-level=high` locally.

Automated dependency updates:

- `.github/dependabot.yml` schedules weekly update PRs for `composer`, `npm`, and `github-actions` ecosystems.

Advisory exception policy:

- A temporarily accepted advisory must be explicit, dated, and carry a removal condition (the action that unblocks the upgrade or the date by which the exception is revisited).
- Record the exception in the pull request that introduces or retains the advisory; do not silence the audit step.
- Exceptions are revisited on every dependabot update PR for the affected package; the audit gate itself never carries an allowlist.

Deployment smoke alias:

- `composer run ops:production-smoke-core`

To enforce blocking merges, configure branch protection for `main` and require status check:

- `Quality Gate / Full Quality Gate`

## Engineering rules

- Project contribution rules (binding policy): `AGENTS.md`
- Cursor/agent rules loader: `.cursorrules`
- Architecture contracts: `docs/ARCHITECTURE.md`
- Navigation maps: `docs/REPO_MAP.md`, `docs/DOMAIN_MAP.md`
- Accepted conventions: `docs/adr/`
- Architecture refactor roadmap (active): `docs/ARCHITECTURE_REFACTOR_NEXT.md`
- Historical architecture roadmap (archived): `docs/ARCHITECTURE_REFACTOR_PLAN.md`
- Execution log: `docs/REFACTORING_EXECUTION_PLAN.md`
- Ops runbook (checkout/webhooks): `docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md`
