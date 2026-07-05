<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Cart;

use App\Domain\Exceptions\CartException;
use App\Domains\Cart\Services\CartResolver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CartResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_for_checkout_requires_guest_token_for_guest_context(): void
    {
        $this->expectException(CartException::class);
        $this->expectExceptionMessage('Guest token is required.');

        app(CartResolver::class)->resolveForCheckout(null, '');
    }

    public function test_resolve_throws_when_authenticated_user_no_longer_exists(): void
    {
        $this->expectException(CartException::class);
        $this->expectExceptionMessage('Authenticated user no longer exists.');

        app(CartResolver::class)->resolve(User::factory()->make(['id' => 999999]));
    }
}
