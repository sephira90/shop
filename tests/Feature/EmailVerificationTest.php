<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
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

    /**
     * Ensure signed verification link returns not found for missing user.
     */
    public function test_signed_verification_link_returns_not_found_for_missing_user(): void
    {
        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => 999999,
            'hash' => sha1('missing@example.com'),
        ]);

        $this->getJson($verificationUrl)
            ->assertNotFound()
            ->assertJsonPath('error.message', 'User not found.');
    }

    /**
     * Ensure signed verification link rejects invalid hash.
     */
    public function test_signed_verification_link_rejects_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->id,
            'hash' => sha1('invalid@example.com'),
        ]);

        $this->getJson($verificationUrl)
            ->assertForbidden()
            ->assertJsonPath('error.message', 'Invalid verification hash.');
    }

    /**
     * Ensure unverified authenticated user can request verification resend.
     */
    public function test_resend_verification_notification_for_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('data.message', 'Verification email has been sent.');
    }

    /**
     * Ensure verified user gets already verified response for resend endpoint.
     */
    public function test_resend_verification_notification_for_verified_user(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('data.message', 'Email already verified.');
    }
}
