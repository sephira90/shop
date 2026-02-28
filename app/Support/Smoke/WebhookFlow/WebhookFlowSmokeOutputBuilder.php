<?php

declare(strict_types=1);

namespace App\Support\Smoke\WebhookFlow;

use App\Support\Smoke\Dto\SmokeCommandOutputDto;
use App\Support\Smoke\SmokeCommandOutputFactory;
use App\Support\Smoke\WebhookFlow\Dto\WebhookFlowSmokeRunResultDto;

final class WebhookFlowSmokeOutputBuilder
{
    public function __construct(
        private readonly SmokeCommandOutputFactory $outputFactory,
    ) {}

    public function build(WebhookFlowSmokeRunResultDto $result): SmokeCommandOutputDto
    {
        return $this->outputFactory->build(
            headers: ['metric', 'value'],
            rows: [
                ['order_id', $result->result->orderId],
                ['payment_id', (string) $result->result->paymentId],
                ['shipment_id', (string) $result->result->shipmentId],
                ['order_status', $result->result->orderStatus],
                ['payment_status', $result->result->paymentStatus],
                ['shipment_status', $result->result->shipmentStatus],
            ],
            successMessage: 'Webhook flow smoke checks passed.',
            rolledBack: $result->rolledBack,
        );
    }
}
