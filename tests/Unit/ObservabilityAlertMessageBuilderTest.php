<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\Dto\ObservabilityAlertPayloadDto;
use App\Support\Observability\ObservabilityAlertMessageBuilder;
use Tests\TestCase;

class ObservabilityAlertMessageBuilderTest extends TestCase
{
    public function test_build_failure_message_formats_subject_and_lines(): void
    {
        config()->set('app.name', 'shop');
        config()->set('app.env', 'testing');
        config()->set('app.url', 'https://shop.test');

        $message = (new ObservabilityAlertMessageBuilder)->buildFailureMessage(new ObservabilityAlertPayloadDto(
            command: 'app:observability-report',
            exitCode: 1,
            output: 'SLO failure output',
            parameters: ['--minutes' => '120', '--source' => 'runtime'],
            happenedAt: '2026-02-28T14:30:00+00:00',
        ));

        $this->assertSame('[shop][testing] Observability SLO check failed: app:observability-report', $message->subject);
        $this->assertSame([
            'Observability SLO check failed.',
            'Environment: testing',
            'App URL: https://shop.test',
            'Command: app:observability-report',
            'Exit code: 1',
            'Timestamp: 2026-02-28T14:30:00+00:00',
            'Parameters: {"--minutes":"120","--source":"runtime"}',
            'Output: SLO failure output',
        ], $message->lines);
    }
}
