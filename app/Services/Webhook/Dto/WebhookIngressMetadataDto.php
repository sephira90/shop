<?php

declare(strict_types=1);

namespace App\Services\Webhook\Dto;

final readonly class WebhookIngressMetadataDto
{
    public function __construct(
        public string $eventId,
    ) {}
}
