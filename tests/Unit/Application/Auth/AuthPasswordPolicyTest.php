<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Auth;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

final class AuthPasswordPolicyTest extends TestCase
{
    public function test_default_password_policy_requires_twelve_characters_letters_and_numbers(): void
    {
        $this->assertPasswordIsRejected('Short123');
        $this->assertPasswordIsRejected('123456789012');
        $this->assertPasswordIsRejected('letterswithoutnumbers');
        $this->assertPasswordIsAccepted('LongPassword12');
    }

    private function assertPasswordIsRejected(string $password): void
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::default()]],
        );

        $this->assertTrue($validator->fails(), "Password [{$password}] must be rejected by the shared policy.");
    }

    private function assertPasswordIsAccepted(string $password): void
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::default()]],
        );

        $this->assertTrue($validator->passes(), "Password [{$password}] must satisfy the shared policy.");
    }
}
