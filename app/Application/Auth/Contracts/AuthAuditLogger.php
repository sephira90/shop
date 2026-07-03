<?php

declare(strict_types=1);

namespace App\Application\Auth\Contracts;

use App\Application\Auth\Support\AuthAuditContext;
use App\Application\Auth\Support\AuthAuditEvent;

interface AuthAuditLogger
{
    /**
     * Emit a structured auth security audit record.
     *
     * Implementations must be side-effect-safe for the caller: an audit
     * emission failure must not abort the auth flow. Secrets (passwords,
     * raw tokens, token ids) must never appear in the context payload.
     */
    public function log(AuthAuditEvent $event, AuthAuditContext $context): void;
}
