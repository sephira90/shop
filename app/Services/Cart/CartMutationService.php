<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Contracts\CartMutationServiceInterface;
use App\Domain\Exceptions\CartException;
use App\Domain\ValueObjects\Money;
use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CartMutationService implements CartMutationServiceInterface
{
    /**
     * Add or update cart item.
     */
    public function upsertItem(
        Cart $cart,
        int $variantId,
        int $quantity,
        ?User $user = null,
        ?string $guestToken = null,
    ): Cart {
        return DB::transaction(function () use ($cart, $variantId, $quantity, $user, $guestToken): Cart {
            $lockedCart = Cart::query()
                ->whereKey($cart->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedCart instanceof Cart) {
                throw CartException::cartNotFound();
            }

            $this->assertOwnership($lockedCart, $user, $guestToken);

            $variant = ProductVariant::query()
                ->with(['inventory', 'product'])
                ->whereKey($variantId)
                ->where('is_active', true)
                ->whereHas('product', static function ($productQuery): void {
                    $productQuery
                        ->where('status', ProductStatus::ACTIVE->value)
                        ->whereNotNull('published_at');
                })
                ->first();

            if (! $variant instanceof ProductVariant) {
                throw CartException::variantNotAvailable();
            }

            $inventory = $variant->inventory;

            if (! $inventory instanceof Inventory || $inventory->availableQuantity() < $quantity) {
                throw CartException::insufficientStockForVariant();
            }

            $item = CartItem::query()
                ->where('cart_id', $lockedCart->id)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if ($quantity <= 0) {
                $item?->delete();

                return $lockedCart->refresh()->load(['items.variant.inventory']);
            }

            $currency = trim((string) $variant->currency);
            if ($currency === '') {
                $currency = trim((string) $lockedCart->currency);
            }

            $unitPrice = Money::fromDecimal((float) $variant->price, $currency);
            $lineTotal = $unitPrice->multiply($quantity);

            $payload = [
                'quantity' => $quantity,
                'unit_price' => $unitPrice->toFloat(),
                'line_total' => $lineTotal->toFloat(),
            ];

            if ($item instanceof CartItem) {
                $item->update($payload);
            } else {
                $lockedCart->items()->create([
                    'product_variant_id' => $variant->id,
                    ...$payload,
                ]);
            }

            return $lockedCart->refresh()->load(['items.variant.inventory']);
        });
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(
        Cart $cart,
        int $variantId,
        ?User $user = null,
        ?string $guestToken = null,
    ): Cart {
        return DB::transaction(function () use ($cart, $variantId, $user, $guestToken): Cart {
            $lockedCart = Cart::query()
                ->whereKey($cart->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedCart instanceof Cart) {
                throw CartException::cartNotFound();
            }

            $this->assertOwnership($lockedCart, $user, $guestToken);

            CartItem::query()
                ->where('cart_id', $lockedCart->id)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->delete();

            return $lockedCart->refresh()->load(['items.variant.inventory']);
        });
    }

    /**
     * Merge guest cart into user cart after authentication.
     */
    public function mergeGuestCart(User $user, string $guestToken): Cart
    {
        return DB::transaction(function () use ($user, $guestToken): Cart {
            $guestCart = Cart::query()
                ->where('guest_token', $guestToken)
                ->where('status', CartStatus::ACTIVE->value)
                ->with('items.variant.inventory')
                ->lockForUpdate()
                ->first();

            $userCart = Cart::query()
                ->where('user_id', $user->id)
                ->where('status', CartStatus::ACTIVE->value)
                ->with('items.variant.inventory')
                ->lockForUpdate()
                ->latest('created_at')
                ->first();

            if (! $userCart instanceof Cart) {
                $userCart = Cart::query()->create([
                    'user_id' => $user->id,
                    'currency' => 'USD',
                    'status' => CartStatus::ACTIVE->value,
                ])->load('items.variant.inventory');
            }

            if ($guestCart === null || $guestCart->id === $userCart->id) {
                return $userCart;
            }

            foreach ($guestCart->items as $item) {
                $existing = $userCart->items->firstWhere('product_variant_id', $item->product_variant_id);
                $existingQuantity = $existing instanceof CartItem ? $existing->quantity : 0;
                $quantity = $existingQuantity + $item->quantity;
                $userCart = $this->upsertItem($userCart, $item->product_variant_id, $quantity);
            }

            $guestCart->update(['status' => CartStatus::ABANDONED->value]);

            return $userCart->refresh()->load(['items.variant.inventory']);
        });
    }

    private function assertOwnership(Cart $cart, ?User $user, ?string $guestToken): void
    {
        $normalizedGuestToken = $this->normalizeGuestToken($guestToken);
        if (! $user instanceof User && $normalizedGuestToken === null) {
            return;
        }

        if ($user instanceof User) {
            if ((int) $cart->user_id !== (int) $user->id) {
                throw CartException::cartOwnershipMismatch();
            }

            return;
        }

        if ($cart->guest_token !== $normalizedGuestToken) {
            throw CartException::cartOwnershipMismatch();
        }
    }

    private function normalizeGuestToken(?string $guestToken): ?string
    {
        if (! is_string($guestToken)) {
            return null;
        }

        $token = trim($guestToken);

        return $token !== '' ? $token : null;
    }
}
