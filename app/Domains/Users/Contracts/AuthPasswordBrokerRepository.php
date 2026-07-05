<?php

declare(strict_types=1);

namespace App\Domains\Users\Contracts;

use App\Domains\Users\Application\Dto\ResetAuthPasswordInputDto;

interface AuthPasswordBrokerRepository
{
    public function sendResetLink(string $email): string;

    public function resetPassword(ResetAuthPasswordInputDto $input): string;

    public function isResetLinkSentStatus(string $status): bool;

    public function isPasswordResetStatus(string $status): bool;
}
