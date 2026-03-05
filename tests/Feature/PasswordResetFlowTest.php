<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Passwords\PasswordBroker as PasswordBrokerImplementation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure forgot-password endpoint sends reset link for existing user.
     */
    public function test_forgot_password_sends_reset_link_for_existing_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'forgot-password@example.com',
        ]);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['message'],
            ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Ensure reset-password endpoint updates user password with valid token.
     */
    public function test_reset_password_updates_user_password(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-password@example.com',
        ]);

        $newPassword = 'new-secure-password';
        $broker = Password::broker();
        self::assertInstanceOf(PasswordBrokerImplementation::class, $broker);
        $token = $broker->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['message'],
            ]);

        $this->assertTrue(Hash::check($newPassword, (string) $user->fresh()?->password));
    }
}
