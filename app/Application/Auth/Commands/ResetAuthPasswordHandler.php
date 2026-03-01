<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Contracts\AuthPasswordBrokerRepository;
use App\Support\Data\TypedValue;
use Symfony\Component\HttpFoundation\Response;

final class ResetAuthPasswordHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthPasswordBrokerRepository $authPasswordBrokerRepository,
    ) {}

    /**
     * Execute reset-password command.
     */
    public function handle(ResetAuthPasswordCommand $command): string
    {
        $status = $this->authPasswordBrokerRepository->resetPassword($command->input);

        if (! $this->authPasswordBrokerRepository->isPasswordResetStatus($status)) {
            throw new AuthApplicationException(
                __($status),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return TypedValue::string(__($status));
    }
}
