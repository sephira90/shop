<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Application\Cart\Dto\CartItemResultDto;
use App\Application\Cart\Dto\CartResultDto;
use App\Application\Cart\Dto\CartSummaryResultDto;
use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CartService
{
    /**
     * Resolve active cart for user or guest token.
     */
    public function resolve(?User $user, ?string $guestToken = null): Cart
    {
        if ($user !== null) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->where('status', CartStatus::ACTIVE->value)
                ->with('items.variant.inventory')
                ->latest('created_at')
                ->first();

            if ($cart instanceof Cart) {
                return $cart;
            }

            return Cart::query()->create([
                'user_id' => $user->id,
                'currency' => 'USD',
                'status' => CartStatus::ACTIVE->value,
            ])->load('items.variant.inventory');
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
            throw new DomainException('Guest token is required.');
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

    /**
     * Add or update cart item.
     */
    public function upsertItem(Cart $cart, int $variantId, int $quantity): Cart
    {
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
            throw new DomainException('Selected variant is not available.');
        }

        $inventory = $variant->inventory;

        if ($inventory === null || $inventory->availableQuantity() < $quantity) {
            throw new DomainException('Insufficient stock for selected variant.');
        }

        /** @var CartItem $item */
        $item = CartItem::query()->updateOrCreate(
            [
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
            ],
            [
                'quantity' => $quantity,
                'unit_price' => $variant->price,
                'line_total' => bcmul((string) $variant->price, (string) $quantity, 2),
            ],
        );

        if ($item->quantity <= 0) {
            $item->delete();
        }

        return $cart->fresh(['items.variant.inventory']);
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(Cart $cart, int $variantId): Cart
    {
        CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->delete();

        return $cart->fresh(['items.variant.inventory']);
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
                $quantity = $existing === null ? $item->quantity : $existing->quantity + $item->quantity;
                $userCart = $this->upsertItem($userCart, $item->product_variant_id, $quantity);
            }

            $guestCart->update(['status' => CartStatus::ABANDONED->value]);

            return $userCart->fresh(['items.variant.inventory']);
        });
    }

    /**
     * Build normalized cart result DTO.
     */
    public function toResultDto(Cart $cart): CartResultDto
    {
        $subtotal = (float) $cart->items->sum('line_total');
        $items = [];

        foreach ($cart->items as $item) {
            if (! $item instanceof CartItem) {
                continue;
            }

            $variant = $item->variant;
            $sku = '';
            $name = '';

            if ($variant !== null) {
                $sku = (string) $variant->sku;
                $name = (string) $variant->name;
            }

            $items[] = new CartItemResultDto(
                productVariantId: (int) $item->product_variant_id,
                sku: $sku,
                name: $name,
                quantity: (int) $item->quantity,
                unitPrice: (float) $item->unit_price,
                lineTotal: (float) $item->line_total,
            );
        }

        return new CartResultDto(
            id: (string) $cart->id,
            guestToken: $cart->guest_token,
            currency: $cart->currency,
            status: $cart->status->value,
            items: $items,
            summary: new CartSummaryResultDto(
                subtotal: $subtotal,
                discountTotal: 0.0,
                shippingTotal: 0.0,
                total: $subtotal,
            ),
        );
    }
}
