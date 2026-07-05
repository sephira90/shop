<?php

declare(strict_types=1);

namespace App\Domains\Cart\Services;

use App\Domain\Exceptions\CartException;
use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CartResolver
{
    /**
     * Resolve active cart for user or guest token.
     */
    public function resolve(?User $user, ?string $guestToken = null): Cart
    {
        if ($user !== null) {
            return DB::transaction(function () use ($user): Cart {
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedUser instanceof User) {
                    throw CartException::authenticatedUserNotFound();
                }

                $cart = Cart::query()
                    ->where('user_id', $lockedUser->id)
                    ->where('status', CartStatus::ACTIVE->value)
                    ->with('items.variant.inventory')
                    ->lockForUpdate()
                    ->latest('created_at')
                    ->first();

                if ($cart instanceof Cart) {
                    return $cart;
                }

                return Cart::query()->create([
                    'user_id' => $lockedUser->id,
                    'currency' => 'USD',
                    'status' => CartStatus::ACTIVE->value,
                ])->load('items.variant.inventory');
            });
        }

        $token = $guestToken ?: Str::lower(Str::random(48));

        return DB::transaction(function () use ($token): Cart {
            $activeCart = Cart::query()
                ->where('guest_token', $token)
                ->where('status', CartStatus::ACTIVE->value)
                ->with('items.variant.inventory')
                ->lockForUpdate()
                ->latest('created_at')
                ->first();

            if ($activeCart instanceof Cart) {
                return $activeCart;
            }

            $previousCart = Cart::query()
                ->where('guest_token', $token)
                ->lockForUpdate()
                ->latest('created_at')
                ->first();

            if ($previousCart instanceof Cart) {
                // Free unique guest token to allow next active cart.
                $previousCart->update(['guest_token' => null]);
            }

            return Cart::query()->create([
                'guest_token' => $token,
                'currency' => 'USD',
                'status' => CartStatus::ACTIVE->value,
            ])->load('items.variant.inventory');
        });
    }

    /**
     * Resolve latest cart for checkout retries.
     */
    public function resolveForCheckout(?User $user, ?string $guestToken = null): Cart
    {
        if ($user !== null) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->with('items.variant.inventory')
                ->latest('created_at')
                ->first();

            if ($cart instanceof Cart) {
                return $cart;
            }

            return $this->resolve($user, null);
        }

        $token = trim((string) $guestToken);
        if ($token === '') {
            throw CartException::guestTokenRequired();
        }

        $cart = Cart::query()
            ->where('guest_token', $token)
            ->with('items.variant.inventory')
            ->latest('created_at')
            ->first();

        if ($cart instanceof Cart) {
            return $cart;
        }

        return Cart::query()->create([
            'guest_token' => $token,
            'currency' => 'USD',
            'status' => CartStatus::ACTIVE->value,
        ])->load('items.variant.inventory');
    }
}
