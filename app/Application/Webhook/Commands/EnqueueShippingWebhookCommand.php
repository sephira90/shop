<?php

declare(strict_types=1);

namespace App\Application\Webhook\Commands;

use App\Support\Data\JsonPayload;

final readonly class EnqueueShippingWebhookCommand
{
    /**
     * Create shipping webhook enqueue command.
     */
    public function __construct(
        public JsonPayload $payload,
        public string $signature,
        public string $receivedAtIso8601,
    ) {}
}
