<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Users;

use App\Domains\Users\Application\AuthAccessTokenIssuer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;
use UnexpectedValueException;

final class AuthAccessTokenIssuerTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_persists_token_with_configured_expiration(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-28T12:00:00+00:00'));
        $user = User::factory()->create();

        $expiration = config('sanctum.expiration');
        \assert(is_int($expiration));

        $plainTextToken = $this->app->make(AuthAccessTokenIssuer::class)->issue($user, 'issuer-test');

        $accessToken = PersonalAccessToken::findToken($plainTextToken);
        $this->assertNotNull($accessToken);
        $this->assertNotNull($accessToken->expires_at);
        $this->assertSame(
            CarbonImmutable::now()->addMinutes($expiration)->toDateTimeString(),
            $accessToken->expires_at->toDateTimeString(),
        );
    }

    public function test_issue_rejects_non_positive_expiration(): void
    {
        $originalExpiration = config('sanctum.expiration');
        config()->set('sanctum.expiration', 0);

        try {
            $this->expectException(UnexpectedValueException::class);
            $this->app->make(AuthAccessTokenIssuer::class)->issue(User::factory()->make(), 'guard-test');
        } finally {
            config()->set('sanctum.expiration', $originalExpiration);
        }
    }
}
