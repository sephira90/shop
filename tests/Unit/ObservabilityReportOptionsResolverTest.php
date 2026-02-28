<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\ObservabilityReportOptionsResolver;
use InvalidArgumentException;
use Tests\TestCase;

class ObservabilityReportOptionsResolverTest extends TestCase
{
    public function test_resolve_defaults_blank_source_from_config(): void
    {
        config()->set('observability.snapshot.default_source', 'smoke');

        $resolver = new ObservabilityReportOptionsResolver;
        $options = $resolver->resolve([
            'minutes' => '60',
            'source' => '',
            'max_api_slow_rate' => null,
            'max_webhook_lag_warn_rate' => null,
            'require_api_samples' => false,
            'require_webhook_samples' => true,
            'json' => true,
        ]);

        $this->assertSame(60, $options->minutes);
        $this->assertSame('smoke', $options->source);
        $this->assertNull($options->maxApiSlowRate);
        $this->assertNull($options->maxWebhookLagWarnRate);
        $this->assertFalse($options->requireApiSamples);
        $this->assertTrue($options->requireWebhookSamples);
        $this->assertTrue($options->json);
    }

    public function test_resolve_rejects_invalid_minutes(): void
    {
        $resolver = new ObservabilityReportOptionsResolver;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --minutes must be between 1 and 1440.');

        $resolver->resolve([
            'minutes' => '0',
            'source' => 'runtime',
            'max_api_slow_rate' => null,
            'max_webhook_lag_warn_rate' => null,
            'require_api_samples' => false,
            'require_webhook_samples' => false,
            'json' => false,
        ]);
    }

    public function test_resolve_rejects_invalid_threshold_format(): void
    {
        $resolver = new ObservabilityReportOptionsResolver;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --max-api-slow-rate must be a number in [0..1].');

        $resolver->resolve([
            'minutes' => '60',
            'source' => 'runtime',
            'max_api_slow_rate' => 'foo',
            'max_webhook_lag_warn_rate' => null,
            'require_api_samples' => false,
            'require_webhook_samples' => false,
            'json' => false,
        ]);
    }

    public function test_resolve_rejects_invalid_source(): void
    {
        $resolver = new ObservabilityReportOptionsResolver;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option --source must be one of: runtime, smoke.');

        $resolver->resolve([
            'minutes' => '60',
            'source' => 'invalid',
            'max_api_slow_rate' => null,
            'max_webhook_lag_warn_rate' => null,
            'require_api_samples' => false,
            'require_webhook_samples' => false,
            'json' => false,
        ]);
    }
}
