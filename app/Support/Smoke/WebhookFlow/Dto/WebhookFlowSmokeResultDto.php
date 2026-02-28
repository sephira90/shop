<?php

declare(strict_types=1);

namespace App\Support\Smoke\WebhookFlow\Dto;

final readonly class WebhookFlowSmokeResultDto
{
    public function __construct(
        public string $orderId,
        public int $paymentId,
        public int $shipmentId,
        public string $orderStatus,
        public string $paymentStatus,
        public string $shipmentStatus,
    ) {}
}
