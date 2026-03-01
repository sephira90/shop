<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Data\TypedValue;
use App\Support\Observability\Dto\ObservabilityAlertMessageDto;
use App\Support\Observability\Dto\ObservabilityAlertPayloadDto;
use Illuminate\Support\Str;

final class ObservabilityAlertMessageBuilder
{
    public function buildFailureMessage(ObservabilityAlertPayloadDto $payload): ObservabilityAlertMessageDto
    {
        $encodedParameters = json_encode($payload->parameters, JSON_UNESCAPED_SLASHES);

        return new ObservabilityAlertMessageDto(
            subject: sprintf(
                '[%s][%s] Observability SLO check failed: %s',
                TypedValue::string(config('app.name', 'app')),
                TypedValue::string(config('app.env', 'unknown')),
                $payload->command,
            ),
            lines: [
                'Observability SLO check failed.',
                'Environment: '.TypedValue::string(config('app.env', 'unknown')),
                'App URL: '.TypedValue::string(config('app.url', 'n/a')),
                'Command: '.$payload->command,
                'Exit code: '.(string) $payload->exitCode,
                'Timestamp: '.$payload->happenedAt,
                'Parameters: '.(is_string($encodedParameters) ? $encodedParameters : '{}'),
                'Output: '.Str::limit($payload->output, 1200),
            ],
        );
    }
}
