<?php

declare(strict_types=1);

namespace App\Services\Webhook;

enum WebhookProcessingOutcome: string
{
    case PROCESSED = 'processed';

    case DUPLICATE = 'duplicate';

    case REJECTED = 'rejected';
}
