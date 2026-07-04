# Order Lifecycle Reconciliation Runbook

Active architecture execution source-of-truth: `docs/ARCHITECTURE_REFACTOR_NEXT.md`.

## Scope

Runbook for production incidents surfaced by `app:orders-reconcile`:

- paid orders whose shipment side effect never dispatched (post-commit dispatch failure or exhausted job retries)
- stale pending payments beyond the operational window (missing capture webhook, abandoned checkout)
- a non-empty `queue.failed_jobs` table (any job exhaustion that affects the order lifecycle)

## Primary Signals

- non-zero exit code from scheduled `app:orders-reconcile`
- routed alert notifications when the command runs with `--route-alerts`
- structured `observability.reconciliation` log records (one per run, with per-detector counts)
- failing `failed_jobs` rows appearing in `queue.failed_jobs`

## Fast Triage (5-10 min)

1. Run the reconciler in table mode to enumerate every finding:

```bash
php artisan app:orders-reconcile
```

2. Run in JSON mode for machine consumption or incident snapshots:

```bash
php artisan app:orders-reconcile --json
```

3. Confirm whether the failure is bounded to a single class of stuck-state by inspecting the `kind` column (`stuck_shipment`, `stale_pending_payment`, `failed_jobs`).

4. Correlate with the SLO alert channel:

```bash
php artisan app:observability-alert-check
```

5. Inspect `queue.failed_jobs` for the specific failed payloads:

```bash
php artisan tinker
>>> DB::table('failed_jobs')->select('id', 'uuid', 'queue', 'failed_at')->get();
```

## Replay Procedure Per Stuck-State Class

Reconciliation is orchestration-only. The replay actions below are owned by existing side-effect jobs that are idempotent by design; re-dispatch is the safe action.

### stuck_shipment (paid order without dispatched shipment)

The `App\Jobs\DispatchShipmentJob` is idempotent: it re-checks `hasCapturedPayment` and delegates to `ShippingService::createShipment`, which guards against duplicate shipment rows.

Replay:

```bash
php artisan tinker
>>> $order = App\Models\Order::where('order_number', 'ORD-...')->firstOrFail();
>>> App\Jobs\DispatchShipmentJob::dispatch($order->id, \Illuminate\Support\Str::uuid()->toString());
```

Verify after replay:

```bash
php artisan app:orders-reconcile --stuck-shipment-minutes=5
```

### stale_pending_payment (pending payment beyond the window)

Investigate first: a pending payment past the window typically indicates a missing webhook capture or an abandoned checkout that never reached the gateway.

1. Check the gateway dashboard for the corresponding transaction.
2. If the capture webhook was never received, request a manual provider replay.
3. If the order was abandoned without payment intent, cancel the order through the order state transition policy.

Reconciliation does not auto-cancel: the action depends on the gateway state.

### failed_jobs (non-empty `queue.failed_jobs`)

1. Inspect each failed job's `payload` and `exception` columns:

```bash
php artisan queue:failed
```

2. Replay a specific failed job by UUID:

```bash
php artisan queue:retry <uuid>
```

3. After successful replay, confirm `queue.failed_jobs` is empty:

```bash
php artisan queue:failed
```

4. Re-run reconciliation:

```bash
php artisan app:orders-reconcile
```

## Threshold And Schedule Configuration

Reconciliation thresholds and schedule are configured via env. Each threshold is a bounded positive integer; an invalid value fails config resolution fast.

- `APP_ORDERS_RECONCILE_ENABLED` — gate for the scheduled run (default `true`)
- `APP_ORDERS_RECONCILE_CRON` — scheduler cadence (default `*/15 * * * *`)
- `ORDERS_RECONCILE_STUCK_SHIPMENT_MINUTES` — paid-without-shipment window (default `90`, max `43200`)
- `ORDERS_RECONCILE_STALE_PENDING_PAYMENT_MINUTES` — pending payment window (default `60`, max `43200`)
- `ORDERS_RECONCILE_FAILED_JOBS_THRESHOLD` — failed_jobs count threshold (default `1`, max `100000`)

## Alert Routing

To route reconciliation findings through the configured alert channels (email/slack/pagerduty), run with `--route-alerts`:

```bash
php artisan app:orders-reconcile --route-alerts
```

The scheduled command does not set `--route-alerts` by default. Operators that want alert delivery on findings should either:

- enable `--route-alerts` in the scheduled invocation, or
- rely on the existing `observability.reconciliation` structured log plus external log-based alerting.

Alert channel configuration is shared with the observability SLO alert flow (see `docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md` → Alert Routing Configuration).

## Escalation Matrix

- `stuck_shipment`: `SEV-2`, owner `fulfillment-oncall`, action `Replay DispatchShipmentJob for each finding; investigate queue backend outage at original placed_at`.
- `stale_pending_payment`: `SEV-3`, owner `payments-oncall`, action `Correlate with gateway; capture webhook or cancel through transition policy`.
- `failed_jobs`: `SEV-2`, owner `platform-oncall`, action `Inspect failed_jobs payload/exception; replay idempotent jobs; fix root cause`.

## Transactional Outbox Escalation

If reconciliation windows prove insufficient (e.g. silent-loss detection time exceeds the operational SLO), the transactional outbox is the explicit escalation path. Adopting the outbox requires separate roadmap approval and is out of scope for the current reconciliation block.

## Post-Incident Checklist

1. Root cause + timeline documented.
2. Thresholds reviewed against measured detection time.
3. Add regression coverage for the discovered failure mode if not already present.
4. Update `docs/REFACTORING_EXECUTION_PLAN.md` progress log if the reconciliation contract changed.
