<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Contracts\AuthPasswordBrokerRepository;
use App\Support\Data\TypedValue;
use Symfony\Component\HttpFoundation\Response;

final class ForgotAuthPasswordHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthPasswordBrokerRepository $authPasswordBrokerRepository,
    ) {}

    /**
     * Execute forgot-password command.
     */
    public function handle(ForgotAuthPasswordCommand $command): string
    {
        $status = $this->authPasswordBrokerRepository->sendResetLink($command->input->email);

        if (! $this->authPasswordBrokerRepository->isResetLinkSentStatus($status)) {
            throw new AuthApplicationException(
                __($status),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return TypedValue::string(__($status));
    }
}
