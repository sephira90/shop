<?php

declare(strict_types=1);

namespace App\Domains\Users\Application;

use RuntimeException;

final class AuthApplicationException extends RuntimeException
{
    /**
     * Create auth application exception with HTTP status code.
     */
    public function __construct(
        string $message,
        public readonly int $statusCode,
    ) {
        parent::__construct($message);
    }
}
