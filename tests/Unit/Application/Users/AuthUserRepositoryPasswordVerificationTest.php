<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Users;

use App\Domains\Users\Repositories\AuthUserRepository;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthUserRepositoryPasswordVerificationTest extends TestCase
{
    public function test_missing_user_verifies_password_against_constant_bcrypt_hash(): void
    {
        Hash::shouldReceive('check')
            ->once()
            ->withArgs(static function (string $plainPassword, string $passwordHash): bool {
                return $plainPassword === 'wrong-password-123'
                    && password_get_info($passwordHash)['algoName'] === 'bcrypt';
            })
            ->andReturnFalse();

        $this->assertFalse(
            (new AuthUserRepository)->isPasswordValid(null, 'wrong-password-123'),
        );
    }
}
