<?php

declare(strict_types=1);

namespace App\Domains\Users\Support;

/**
 * Stable auth audit event taxonomy.
 *
 * Literal values are a machine-readable contract: they are emitted as the
 * log message `auth.audit.<value>` and must not be renamed without a
 * coordinated migration of the observability consumers.
 */
enum AuthAuditEvent: string
{
    case LoginSucceeded = 'login.succeeded';
    case LoginFailed = 'login.failed';
    case Logout = 'logout';
    case TokenIssued = 'token.issued';
    case TokenRevoked = 'token.revoked';
    case PasswordResetRequested = 'password.reset.requested';
    case PasswordResetCompleted = 'password.reset.completed';
    case EmailVerified = 'email.verified';
}
