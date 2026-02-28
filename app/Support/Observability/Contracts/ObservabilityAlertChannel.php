<?php

declare(strict_types=1);

namespace App\Support\Observability\Contracts;

use App\Support\Observability\Dto\ObservabilityAlertMessageDto;

interface ObservabilityAlertChannel
{
    public function channel(): string;

    public function send(ObservabilityAlertMessageDto $message): bool;
}
