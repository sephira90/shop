<?php

declare(strict_types=1);

namespace Tests\Support\Auth;

use App\Domains\Users\Contracts\AuthAuditLogger;
use App\Domains\Users\Support\AuthAuditContext;
use App\Domains\Users\Support\AuthAuditEvent;

/**
 * In-memory AuthAuditLogger spy used by feature tests to assert which audit
 * records were emitted and with what context.
 */
final class AuthAuditLoggerSpy implements AuthAuditLogger
{
    /** @var list<array{event:AuthAuditEvent, context:AuthAuditContext}> */
    private array $emitted = [];

    public function log(AuthAuditEvent $event, AuthAuditContext $context): void
    {
        $this->emitted[] = ['event' => $event, 'context' => $context];
    }

    /**
     * @return list<string>
     */
    public function eventNames(): array
    {
        return array_map(
            static fn (array $entry): string => $entry['event']->value,
            $this->emitted,
        );
    }

    public function hasEvent(string $eventValue): bool
    {
        foreach ($this->emitted as $entry) {
            if ($entry['event']->value === $eventValue) {
                return true;
            }
        }

        return false;
    }

    public function contextFor(string $eventValue): AuthAuditContext
    {
        foreach ($this->emitted as $entry) {
            if ($entry['event']->value === $eventValue) {
                return $entry['context'];
            }
        }

        throw new \RuntimeException("Expected audit event [auth.audit.{$eventValue}] was not emitted.");
    }
}
