<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure user can register and fetch profile.
     */
    public function test_register_and_me_flow(): void
    {

        $this->seed(RoleSeeder::class);
        $register = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'verysecurepassword12',
            'password_confirmation' => 'verysecurepassword12',
        ]);

        $register->assertCreated();
        $token = $this->jsonString($register, 'data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'john@example.com');
    }

    /**
     * Ensure logout revokes only the current bearer token.
     */
    public function test_logout_revokes_current_access_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('browser')->plainTextToken;
        $otherToken = $user->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.message', 'Logged out successfully.');

        $this->assertNull(PersonalAccessToken::findToken($token));
        $this->assertNotNull(PersonalAccessToken::findToken($otherToken));

        Auth::forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        Auth::forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    public function test_login_issues_expiring_token_and_expired_token_is_rejected(): void
    {
        $originalExpiration = config('sanctum.expiration');
        config()->set('sanctum.expiration', 60);

        try {
            $now = CarbonImmutable::parse('2026-06-28T12:00:00+00:00');
            $this->travelTo($now);

            $user = User::factory()->create([
                'email' => 'expiring-token@example.com',
            ]);

            $response = $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'password',
                'device_name' => 'security-test',
            ])->assertOk();

            $plainTextToken = $this->jsonString($response, 'data.token');
            $accessToken = PersonalAccessToken::findToken($plainTextToken);

            $this->assertNotNull($accessToken);
            $this->assertNotNull($accessToken->expires_at);
            $this->assertSame(
                $now->addMinutes(60)->toDateTimeString(),
                $accessToken->expires_at->toDateTimeString(),
            );

            $accessToken->forceFill([
                'created_at' => $now->subMinutes(61),
                'expires_at' => $now->subMinute(),
            ])->save();

            $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
                ->getJson('/api/v1/auth/me')
                ->assertUnauthorized();
        } finally {
            config()->set('sanctum.expiration', $originalExpiration);
        }
    }

    public function test_inactive_user_token_is_rejected_and_all_tokens_are_revoked(): void
    {
        $user = User::factory()->create();
        $browserToken = $user->createToken('browser')->plainTextToken;
        $mobileToken = $user->createToken('mobile')->plainTextToken;

        $user->forceFill(['is_active' => false])->save();

        $this->withHeader('Authorization', 'Bearer '.$browserToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.message', 'Unauthenticated.')
            ->assertJsonPath('error.type', 'AuthenticationException');

        $this->assertNull(PersonalAccessToken::findToken($browserToken));
        $this->assertNull(PersonalAccessToken::findToken($mobileToken));
    }

    public function test_inactive_bearer_token_is_rejected_on_guest_capable_cart_route(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('browser')->plainTextToken;

        $user->forceFill(['is_active' => false])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/cart')
            ->assertUnauthorized()
            ->assertJsonPath('error.message', 'Unauthenticated.')
            ->assertJsonPath('error.type', 'AuthenticationException');

        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    public function test_login_rejects_inactive_user_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive-login@example.com',
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'disabled-login-test',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Invalid credentials.')
            ->assertJsonPath('error.type', 'AuthApplicationException');
    }

    public function test_guest_cart_request_without_bearer_passes_active_user_middleware(): void
    {
        $this->getJson('/api/v1/cart?guest_token=guest-active-middleware-test')
            ->assertOk();
    }

    public function test_register_rejects_weak_password_matrix(): void
    {
        $weakPasswords = [
            'Short123',
            '123456789012',
            'letterswithoutnumbers',
        ];

        foreach ($weakPasswords as $index => $password) {
            $this->postJson('/api/v1/auth/register', [
                'first_name' => 'Weak',
                'last_name' => 'Password',
                'email' => "weak-password-{$index}@example.com",
                'password' => $password,
                'password_confirmation' => $password,
            ])
                ->assertUnprocessable()
                ->assertJsonPath('error.type', 'ValidationException')
                ->assertJsonStructure(['error' => ['validation' => ['password']]]);
        }
    }

    public function test_login_lockout_is_scoped_to_normalized_email_and_client_ip(): void
    {
        config()->set('auth.login_throttle.max_attempts', 2);
        config()->set('auth.login_throttle.decay_seconds', 60);

        $payload = [
            'email' => ' Lockout@Example.com ',
            'password' => 'wrong-password-123',
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/v1/auth/login', $payload)
            ->assertUnprocessable();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/v1/auth/login', [
                ...$payload,
                'email' => 'lockout@example.COM',
            ])
            ->assertUnprocessable();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/v1/auth/login', $payload)
            ->assertTooManyRequests();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->postJson('/api/v1/auth/login', $payload)
            ->assertUnprocessable();
    }

    public function test_known_and_unknown_login_failures_return_byte_identical_envelopes(): void
    {
        User::factory()->create(['email' => 'known@example.com']);

        $headers = ['X-Correlation-Id' => 'auth-envelope-parity'];
        $knownResponse = $this->withHeaders($headers)->postJson('/api/v1/auth/login', [
            'email' => 'known@example.com',
            'password' => 'wrong-password-123',
        ]);
        $unknownResponse = $this->withHeaders($headers)->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'wrong-password-123',
        ]);

        $knownResponse->assertUnprocessable();
        $unknownResponse->assertUnprocessable();
        $this->assertSame($knownResponse->getContent(), $unknownResponse->getContent());
    }
}
