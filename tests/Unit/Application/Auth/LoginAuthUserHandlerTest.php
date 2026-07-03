<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Auth;

use App\Application\Auth\AuthAccessTokenIssuer;
use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Commands\LoginAuthUserCommand;
use App\Application\Auth\Commands\LoginAuthUserHandler;
use App\Application\Auth\Contracts\AuthUserRepository;
use App\Application\Auth\Dto\LoginAuthInputDto;
use App\Application\Auth\Support\AuthUserDtoMapper;
use App\Contracts\CartServiceInterface;
use Mockery;
use Mockery\CompositeExpectation;
use Mockery\MockInterface;
use Tests\TestCase;

final class LoginAuthUserHandlerTest extends TestCase
{
    public function test_unknown_email_still_performs_exactly_one_password_verification(): void
    {
        /** @var AuthUserRepository&MockInterface $repository */
        $repository = Mockery::mock(AuthUserRepository::class);
        $findExpectation = $repository->shouldReceive('findByEmail');
        self::assertInstanceOf(CompositeExpectation::class, $findExpectation);
        $findExpectation->__call('once', []);
        $findExpectation->__call('with', ['missing@example.com'])->andReturn(null);
        $passwordExpectation = $repository->shouldReceive('isPasswordValid');
        self::assertInstanceOf(CompositeExpectation::class, $passwordExpectation);
        $passwordExpectation->__call('once', []);
        $passwordExpectation->__call('with', [null, 'wrong-password-123'])->andReturn(false);

        $cartService = Mockery::mock(CartServiceInterface::class);
        $cartService->shouldNotReceive('mergeGuestCart');
        /** @var CartServiceInterface&MockInterface $cartService */
        $handler = new LoginAuthUserHandler(
            $repository,
            new AuthAccessTokenIssuer($repository),
            $cartService,
            new AuthUserDtoMapper,
        );

        $this->expectException(AuthApplicationException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $handler->handle(new LoginAuthUserCommand(new LoginAuthInputDto(
            email: 'missing@example.com',
            password: 'wrong-password-123',
            deviceName: null,
            guestToken: null,
        )));
    }
}
