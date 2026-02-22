# Checkout and Webhook Incident Runbook

## Scope

Runbook for production incidents affecting checkout flow and webhook processing:
- checkout errors/timeouts
- payment/shipping webhook lag, duplicates, rejected events
- uncontrolled growth of lifecycle tables (`checkout_idempotencies`, `webhook_receipts`, `carts`)

## Primary Signals

- SLO alerts from scheduled `app:observability-report` checks
- routed alert notifications from `app:observability-alert-check` (email/slack/pagerduty)
- failing smoke checks (`app:webhook-flow-smoke`, `app:api-contract-smoke`)
- order creation drop or spike in webhook retries/duplicates

## Fast Triage (5-10 min)

1. Baseline health:
```bash
php artisan app:healthcheck
```
2. API contract sanity:
```bash
php artisan app:api-contract-smoke
```
3. End-to-end webhook chain:
```bash
php artisan app:webhook-flow-smoke
```
4. SLO snapshot (blocking thresholds):
```bash
php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples
```
5. Alert-routing wrapper check:
```bash
php artisan app:observability-alert-check
```
6. Tabletop drill smoke (dry-run path):
```bash
php artisan app:oncall-drill-smoke
```

If steps 2-4 fail, incident is `SEV-1/SEV-2` for checkout/webhooks.

## Checkout Incident Flow

1. Confirm impact: elevated API slow/fail rate in observability report.
2. Verify DB/queue/cache connectivity.
3. Re-run `app:api-contract-smoke` after infra mitigation.
4. Validate order placement path manually with one safe test checkout.
5. Keep checkout open only after smoke checks are green.

## Webhook Incident Flow

1. Confirm provider impact (`payment` or `shipping`) in observability report.
2. Run `app:webhook-flow-smoke` to localize break point.
3. Check duplicate/rejected growth and endpoint auth/signature validity.
4. Replay only idempotent-safe events after root cause is fixed.
5. Re-run smoke and observability commands before closing incident.

## Alert Routing Configuration

Operational alert channels are configured via env:
- email: `APP_OBSERVABILITY_ALERTS_EMAIL_ENABLED`, `APP_OBSERVABILITY_ALERTS_EMAIL_RECIPIENTS`
- slack: `APP_OBSERVABILITY_ALERTS_SLACK_ENABLED`, `APP_OBSERVABILITY_ALERTS_SLACK_WEBHOOK_URL`
- pagerduty: `APP_OBSERVABILITY_ALERTS_PAGERDUTY_ENABLED`, `APP_OBSERVABILITY_ALERTS_PAGERDUTY_INTEGRATION_KEY`, `APP_OBSERVABILITY_ALERTS_PAGERDUTY_SEVERITY`
- throttling: `APP_OBSERVABILITY_ALERTS_COOLDOWN_MINUTES`

## Tabletop Drill

Use command:
```bash
php artisan app:oncall-drill-smoke
```

Optional extended drill (includes write-path smokes):
```bash
php artisan app:oncall-drill-smoke --with-write-smokes
```

Expected dry-run outcomes:
- `oncall_healthcheck` -> `status=ok`
- `oncall_observability_slo_report` -> `status=ok`
- `oncall_cleanup_dry_run` -> `status=ok`

Expected extended outcomes:
- `oncall_api_contract_smoke` -> `status=ok`
- `oncall_webhook_flow_smoke` -> `status=ok`

If any check fails, command prints escalation routing table and returns non-zero exit code.

## Drill Scheduling

Regular drill scheduling is configured via env:
- `APP_ONCALL_DRILL_ENABLED`
- `APP_ONCALL_DRILL_CRON`
- `APP_ONCALL_DRILL_WITH_WRITE_SMOKES`
- `APP_ONCALL_DRILL_PERSIST`

## Escalation Matrix

- `oncall_healthcheck`: `SEV-1`, owner `platform-oncall`, action `Stabilize db/cache connectivity and re-run healthcheck`.
- `oncall_observability_slo_report`: `SEV-2`, owner `api-oncall`, action `Run app:observability-alert-check and investigate API/webhook SLO regression`.
- `oncall_cleanup_dry_run`: `SEV-3`, owner `backend-oncall`, action `Validate lifecycle tables and cleanup retention config`.
- `oncall_api_contract_smoke`: `SEV-2`, owner `api-oncall`, action `Investigate API contract regression before enabling checkout traffic changes`.
- `oncall_webhook_flow_smoke`: `SEV-2`, owner `fulfillment-oncall`, action `Investigate payment/shipping webhook chain and idempotency flow`.

## Lifecycle Table Cleanup

Dry-run first:
```bash
php artisan app:maintenance-cleanup --dry-run
```

Execute cleanup:
```bash
php artisan app:maintenance-cleanup
```

Override retention only as incident mitigation (with approval):
```bash
php artisan app:maintenance-cleanup --idempotency-retain-hours=24 --webhook-retain-hours=24 --active-cart-retain-hours=24 --inactive-cart-retain-hours=24
```

## Post-Incident Checklist

1. Root cause + timeline documented.
2. Thresholds and retention windows reviewed.
3. Add regression test/smoke if gap found.
4. Update `docs/REFACTORING_EXECUTION_PLAN.md` progress log if architecture/process changed.
