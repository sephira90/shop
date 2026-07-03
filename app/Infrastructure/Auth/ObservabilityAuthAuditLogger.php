<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Application\Auth\Contracts\AuthAuditLogger;
use App\Application\Auth\Support\AuthAuditContext;
use App\Application\Auth\Support\AuthAuditEvent;
use App\Support\Data\TypedValue;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Observability-channel auth audit logger.
 *
 * Writes structured `auth.audit.<event>` records to the configured
 * observability log channel. Emission failures are reported through the
 * default log channel as `auth.audit_emission_failed` and never propagated,
 * so an audit logging fault cannot abort an auth flow.
 */
final class ObservabilityAuthAuditLogger implements AuthAuditLogger
{
    /**
     * Resolve the configured observability channel, mirroring ObservabilityService.
     */
    public function log(AuthAuditEvent $event, AuthAuditContext $context): void
    {
        $message = 'auth.audit.'.$event->value;
        $payload = $context->toLogArray();

        try {
            $this->logger()->info($message, $payload);
        } catch (Throwable $failure) {
            // Audit emission must never break the auth flow; surface the
            // infrastructure failure through the default channel without PII.
            Log::error('auth.audit_emission_failed', [
                'event' => $event->value,
                'reason' => $failure::class,
            ]);
        }
    }

    private function logger(): LoggerInterface
    {
        return Log::channel(
            TypedValue::string(config('observability.channel', config('logging.default', 'stack'))),
        );
    }
}
