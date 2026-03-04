<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Exceptions\CartException;
use App\Services\Cart\CartResolver;
use Tests\TestCase;

final class CartResolverTest extends TestCase
{
    public function test_resolve_for_checkout_requires_guest_token_for_guest_context(): void
    {
        $this->expectException(CartException::class);
        $this->expectExceptionMessage('Guest token is required.');

        app(CartResolver::class)->resolveForCheckout(null, '');
    }
}
