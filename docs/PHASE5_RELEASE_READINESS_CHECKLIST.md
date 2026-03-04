# Phase 5 Release Readiness Checklist

Date: `2026-02-22`
Owner: `backend/frontend platform team`
Scope: `Operations & Maintenance closeout`

Active architecture execution source-of-truth: `docs/ARCHITECTURE_REFACTOR_NEXT.md`.

## Exit Criteria Validation

- [x] Controlled growth of service tables is enforced:
  - Scheduled cleanup command exists: `app:maintenance-cleanup`
  - Retention windows are config-driven: `config/cleanup.php`
  - Scheduler wiring is active with overlap protection: `routes/console.php`
  - Cleanup command has feature coverage: `tests/Feature/AppMaintenanceCleanupCommandTest.php`
- [x] Operational monitoring is transparent and actionable:
  - SLO report command is available: `app:observability-report`
  - Alert-check wrapper is available: `app:observability-alert-check`
  - Alert routing supports email/slack/pagerduty with cooldown:
    - `app/Support/Observability/ObservabilityAlertRouter.php`
    - `app/Notifications/ObservabilitySloFailureNotification.php`
  - Tabletop/on-call drill command exists: `app:oncall-drill-smoke`
  - Runbook with escalation matrix exists: `docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md`

## Configuration Readiness

- [x] Local/stage/prod/testing env templates include cleanup and observability alert keys:
  - `.env.example`
  - `.env.stage.example`
  - `.env.prod.example`
  - `.env.testing`
- [x] Scheduler is configured for:
  - `app:maintenance-cleanup`
  - `app:observability-alert-check`
  - `app:oncall-drill-smoke`

## Verification Gates

- [x] Backend quality gates are green:
  - canonical alias: `composer run quality:backend`
  - expands to: lint + static analysis + backend test suite
  - static analysis alias: `composer run analyse`
  - static analysis expands to: `composer run analyse:phpstan` + `composer run analyse:psalm`
- [x] Frontend quality gates are green:
  - canonical alias: `composer run quality:frontend`
  - expands to: lint + oxlint + format check + type-check + frontend test suite + production build
- [x] Production-oriented smoke/ops checks are green:
  - cache clear alias: `composer run ops:clear`
  - route smoke alias: `composer run ops:routes-smoke`
  - observability report alias: `composer run ops:observability-report`
  - CI alias: `composer run ops:ci-production-smoke`
  - deploy alias: `composer run ops:production-smoke-core`
  - docker aliases:
    - `composer run ops:docker-up`
    - `composer run ops:docker-down`
    - `composer run ops:docker-bootstrap`
  - deploy alias expands to: `app:healthcheck`, `app:performance-smoke`, `app:webhook-flow-smoke`, `app:api-contract-smoke`, `app:observability-report`
  - `php artisan app:observability-alert-check`
  - `php artisan app:oncall-drill-smoke`

## Go/No-Go

- [x] `GO`: `Phase 5` objectives and exit criteria are satisfied.
- [x] Periodic `app:oncall-drill-smoke` scheduling is enabled via env-guarded scheduler wiring.
