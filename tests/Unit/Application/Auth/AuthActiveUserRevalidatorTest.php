<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Auth;

use App\Application\Auth\AuthActiveUserRevalidator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class AuthActiveUserRevalidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_revalidate_active_user_returns_true_without_revoking_tokens(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $plainTextToken = $user->createToken('active-session')->plainTextToken;

        $result = $this->app->make(AuthActiveUserRevalidator::class)->revalidate($user);

        $this->assertTrue($result);
        $this->assertNotNull(PersonalAccessToken::findToken($plainTextToken));
    }

    public function test_revalidate_inactive_user_returns_false_and_revokes_all_tokens(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $browserToken = $user->createToken('browser')->plainTextToken;
        $mobileToken = $user->createToken('mobile')->plainTextToken;

        $result = $this->app->make(AuthActiveUserRevalidator::class)->revalidate($user);

        $this->assertFalse($result);
        $this->assertNull(PersonalAccessToken::findToken($browserToken));
        $this->assertNull(PersonalAccessToken::findToken($mobileToken));
    }
}
