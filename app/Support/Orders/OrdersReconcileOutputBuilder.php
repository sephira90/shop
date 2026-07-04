<?php

declare(strict_types=1);

namespace App\Support\Orders;

use App\Support\Orders\Dto\OrdersReconcileFindingDto;
use App\Support\Orders\Dto\OrdersReconcileOutputDto;
use App\Support\Orders\Dto\OrdersReconcileRunResultDto;
use RuntimeException;

final class OrdersReconcileOutputBuilder
{
    public function build(OrdersReconcileRunResultDto $result): OrdersReconcileOutputDto
    {
        if ($result->options->json) {
            return $this->buildJson($result);
        }

        return $this->buildTable($result);
    }

    private function buildTable(OrdersReconcileRunResultDto $result): OrdersReconcileOutputDto
    {
        $rows = [];

        foreach ($result->stuckShipments as $finding) {
            $rows[] = $this->orderFindingRow($finding);
        }

        foreach ($result->stalePendingPayments as $finding) {
            $rows[] = $this->orderFindingRow($finding);
        }

        foreach ($result->failedJobs as $finding) {
            $rows[] = ['failed_jobs', null, null, $finding->count === null ? null : (string) $finding->count];
        }

        return new OrdersReconcileOutputDto(
            jsonOutput: null,
            findingsHeaders: ['kind', 'order_number', 'age_minutes', 'count'],
            findingsRows: $rows,
            cleanMessage: $result->isClean() ? 'No order lifecycle stuck-state findings.' : null,
        );
    }

    /**
     * @return list<string|null>
     */
    private function orderFindingRow(OrdersReconcileFindingDto $finding): array
    {
        return [
            $finding->kind,
            $finding->orderNumber,
            $finding->ageMinutes === null ? null : (string) $finding->ageMinutes,
            null,
        ];
    }

    private function buildJson(OrdersReconcileRunResultDto $result): OrdersReconcileOutputDto
    {
        $payload = [
            'clean' => $result->isClean(),
            'stuck_shipments' => $this->serializeOrderFindings($result->stuckShipments),
            'stale_pending_payments' => $this->serializeOrderFindings($result->stalePendingPayments),
            'failed_jobs' => array_map(
                static fn (OrdersReconcileFindingDto $finding): array => ['count' => $finding->count],
                $result->failedJobs,
            ),
        ];

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! is_string($encoded)) {
            throw new RuntimeException('Unable to encode orders reconciliation payload.');
        }

        return new OrdersReconcileOutputDto(
            jsonOutput: $encoded,
            findingsHeaders: [],
            findingsRows: [],
            cleanMessage: null,
        );
    }

    /**
     * @param  list<OrdersReconcileFindingDto>  $findings
     * @return list<array{order_id:string|null, order_number:string|null, age_minutes:int|null}>
     */
    private function serializeOrderFindings(array $findings): array
    {
        return array_map(
            static fn (OrdersReconcileFindingDto $finding): array => [
                'order_id' => $finding->orderId,
                'order_number' => $finding->orderNumber,
                'age_minutes' => $finding->ageMinutes,
            ],
            $findings,
        );
    }
}
