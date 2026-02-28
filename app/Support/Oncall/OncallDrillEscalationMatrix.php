<?php

declare(strict_types=1);

namespace App\Support\Oncall;

final class OncallDrillEscalationMatrix
{
    /**
     * @return array{severity:string,owner:string,next_step:string}
     */
    public function forCheck(string $check): array
    {
        return match ($check) {
            'oncall_healthcheck' => [
                'severity' => 'SEV-1',
                'owner' => 'platform-oncall',
                'next_step' => 'Stabilize db/cache connectivity and re-run healthcheck.',
            ],
            'oncall_observability_slo_report' => [
                'severity' => 'SEV-2',
                'owner' => 'api-oncall',
                'next_step' => 'Run app:observability-alert-check and investigate API/webhook SLO regression.',
            ],
            'oncall_cleanup_dry_run' => [
                'severity' => 'SEV-3',
                'owner' => 'backend-oncall',
                'next_step' => 'Validate lifecycle tables and cleanup retention config.',
            ],
            'oncall_api_contract_smoke' => [
                'severity' => 'SEV-2',
                'owner' => 'api-oncall',
                'next_step' => 'Investigate API contract regression before enabling checkout traffic changes.',
            ],
            'oncall_webhook_flow_smoke' => [
                'severity' => 'SEV-2',
                'owner' => 'fulfillment-oncall',
                'next_step' => 'Investigate payment/shipping webhook chain and idempotency flow.',
            ],
            default => [
                'severity' => 'SEV-3',
                'owner' => 'oncall',
                'next_step' => 'Review command output and run targeted diagnostics.',
            ],
        };
    }
}
