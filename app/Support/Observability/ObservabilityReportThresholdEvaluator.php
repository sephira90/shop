<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Observability\Dto\ObservabilityReportEvaluationResultDto;
use App\Support\Observability\Dto\ObservabilityReportOptionsDto;

final class ObservabilityReportThresholdEvaluator
{
    /**
     * @param  array{
     *     minutes:int,
     *     source:string,
     *     api:array{count:int,avg_duration_ms:float,slow_count:int},
     *     catalog:list<array{
     *         segment:string,
     *         count:int,
     *         hit_count:int,
     *         miss_count:int,
     *         hit_ratio:float,
     *         avg_duration_ms:float,
     *         slow_miss_count:int
     *     }>,
     *     webhook:list<array{
     *         provider:string,
     *         count:int,
     *         processed_count:int,
     *         duplicate_count:int,
     *         rejected_count:int,
     *         avg_duration_ms:float,
     *         avg_lag_ms:?float,
     *         lag_warn_count:int
     *     }>
     * }  $snapshot
     */
    public function evaluate(array $snapshot, ObservabilityReportOptionsDto $options): ObservabilityReportEvaluationResultDto
    {
        $warnings = [];
        $violations = [];

        if ($options->requireApiSamples && (int) $snapshot['api']['count'] <= 0) {
            $violations[] = 'Required API samples are missing in selected window.';
        }

        if ($options->requireWebhookSamples) {
            $webhookSamples = 0;

            foreach ($snapshot['webhook'] as $row) {
                $webhookSamples += (int) $row['count'];
            }

            if ($webhookSamples <= 0) {
                $violations[] = 'Required webhook samples are missing in selected window.';
            }
        }

        if ($options->maxApiSlowRate !== null) {
            $apiCount = (int) $snapshot['api']['count'];

            if ($apiCount > 0) {
                $slowCount = (int) $snapshot['api']['slow_count'];
                $slowRate = $slowCount / $apiCount;

                if ($slowRate > $options->maxApiSlowRate) {
                    $violations[] = sprintf(
                        'API slow rate exceeded: %.4f > %.4f (%d/%d).',
                        $slowRate,
                        $options->maxApiSlowRate,
                        $slowCount,
                        $apiCount,
                    );
                }
            } else {
                $warnings[] = 'API threshold check skipped: no API samples in selected window.';
            }
        }

        if ($options->maxWebhookLagWarnRate !== null) {
            $checkedProviders = 0;

            foreach ($snapshot['webhook'] as $row) {
                $count = (int) $row['count'];

                if ($count <= 0) {
                    continue;
                }

                $checkedProviders++;
                $lagWarnCount = (int) $row['lag_warn_count'];
                $lagWarnRate = $lagWarnCount / $count;

                if ($lagWarnRate > $options->maxWebhookLagWarnRate) {
                    $violations[] = sprintf(
                        'Webhook lag-warn rate exceeded for provider %s: %.4f > %.4f (%d/%d).',
                        (string) $row['provider'],
                        $lagWarnRate,
                        $options->maxWebhookLagWarnRate,
                        $lagWarnCount,
                        $count,
                    );
                }
            }

            if ($checkedProviders === 0) {
                $warnings[] = 'Webhook lag threshold check skipped: no webhook samples in selected window.';
            }
        }

        return new ObservabilityReportEvaluationResultDto($warnings, $violations);
    }
}
