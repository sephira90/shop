# Phase 5 Release Readiness Checklist

Date: `2026-02-22`
Owner: `backend/frontend platform team`
Scope: `Operations & Maintenance closeout`

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
  - `composer run lint`
  - `composer run analyse`
  - `php artisan test`
- [x] Frontend quality gates are green:
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
- [x] Production-oriented smoke/ops checks are green:
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
  - `php artisan app:observability-alert-check`
  - `php artisan app:oncall-drill-smoke`

## Go/No-Go

- [x] `GO`: `Phase 5` objectives and exit criteria are satisfied.
- [x] Periodic `app:oncall-drill-smoke` scheduling is enabled via env-guarded scheduler wiring.
