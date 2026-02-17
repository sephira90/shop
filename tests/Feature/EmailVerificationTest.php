<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure email verification link works without bearer token.
     */
    public function test_signed_verification_link_verifies_user(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->getJson($verificationUrl)
            ->assertOk()
            ->assertJsonPath('data.message', 'Email verified successfully.');

        $this->assertTrue((bool) $user->fresh()?->hasVerifiedEmail());
    }
}
