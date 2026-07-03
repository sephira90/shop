<?php

declare(strict_types=1);

namespace App\Support\Observability;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Resolve the current correlation id across HTTP, queue, and console contexts.
 *
 * HTTP requests reuse the X-Correlation-Id header attached by
 * CorrelationIdMiddleware; queue and console contexts resolve to null because
 * no request boundary carries the header, so callers generate a stable id at
 * dispatch time and pass it through the job payload instead.
 */
final readonly class CorrelationContext
{
    private const string HEADER = 'X-Correlation-Id';

    /**
     * Resolve the inbound correlation id, or null when no request boundary carries one.
     */
    public function current(): ?string
    {
        $value = app(Request::class)->headers->get(self::HEADER);

        return $value === null || $value === '' ? null : $value;
    }

    /**
     * Resolve the inbound correlation id, or generate a new UUID when none is present.
     */
    public function currentOrNew(): string
    {
        return $this->current() ?? Str::uuid()->toString();
    }
}
