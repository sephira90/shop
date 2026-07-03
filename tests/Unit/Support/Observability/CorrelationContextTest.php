<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability;

use App\Support\Observability\CorrelationContext;
use Illuminate\Http\Request;
use Tests\TestCase;

final class CorrelationContextTest extends TestCase
{
    public function test_current_returns_null_when_request_has_no_correlation_header(): void
    {
        $this->app->instance(Request::class, Request::create('/api/v1/ping'));

        $context = $this->app->make(CorrelationContext::class);

        $this->assertNull($context->current());
    }

    public function test_current_returns_header_value_when_correlation_header_is_present(): void
    {
        $request = Request::create('/api/v1/ping');
        $request->headers->set('X-Correlation-Id', 'ingress-cid-123');

        $this->app->instance(Request::class, $request);

        $context = $this->app->make(CorrelationContext::class);

        $this->assertSame('ingress-cid-123', $context->current());
    }

    public function test_current_treats_empty_header_as_absent(): void
    {
        $request = Request::create('/api/v1/ping');
        $request->headers->set('X-Correlation-Id', '');

        $this->app->instance(Request::class, $request);

        $context = $this->app->make(CorrelationContext::class);

        $this->assertNull($context->current());
    }

    public function test_current_or_new_returns_header_value_when_correlation_header_is_present(): void
    {
        $request = Request::create('/api/v1/ping');
        $request->headers->set('X-Correlation-Id', 'ingress-cid-456');

        $this->app->instance(Request::class, $request);

        $context = $this->app->make(CorrelationContext::class);

        $this->assertSame('ingress-cid-456', $context->currentOrNew());
    }

    public function test_current_or_new_generates_a_stable_uuid_when_no_correlation_header_is_present(): void
    {
        $this->app->instance(Request::class, Request::create('/api/v1/ping'));

        $context = $this->app->make(CorrelationContext::class);
        $generated = $context->currentOrNew();

        $this->assertNotSame('', $generated);
        // UUID v4 string form: 8-4-4-4-12 hex groups.
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $generated,
        );
    }
}
