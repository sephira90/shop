<?php

declare(strict_types=1);

namespace App\Services\Payment\Dto;

use App\Enums\PaymentStatus;
use App\Support\Data\JsonPayload;

final readonly class PaymentCreationResultDto
{
    public function __construct(
        public string $transactionId,
        public PaymentStatus $status,
        public JsonPayload $payload,
    ) {}

    /**
     * Convert DTO to transport-friendly payload.
     *
     * @return array{transaction_id:string,status:string,payload:array<string,mixed>}
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'status' => $this->status->value,
            'payload' => $this->payload->toArray(),
        ];
    }
}
