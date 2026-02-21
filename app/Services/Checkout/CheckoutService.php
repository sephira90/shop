<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\PromotionType;
use App\Enums\ShipmentStatus;
use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutIdempotency;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CheckoutService
{
    /**
     * Create order from cart with idempotency key.
     *
     * @param  array<string, mixed>  $checkoutPayload
     */
    public function placeOrder(
        Cart $cart,
        array $checkoutPayload,
        string $idempotencyKey,
        ?User $user = null,
    ): Order {
        $scopeKey = $this->resolveScopeKey($cart, $user);
        $hash = hash('sha256', json_encode([$cart->id, $checkoutPayload], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($cart, $checkoutPayload, $idempotencyKey, $hash, $scopeKey, $user): Order {
            $lockedCart = Cart::query()
                ->whereKey($cart->id)
                ->with(['items.variant.inventory'])
                ->lockForUpdate()
                ->first();

            if (! $lockedCart instanceof Cart) {
                throw new DomainException('Cart not found.');
            }

            $idempotency = CheckoutIdempotency::query()
                ->where('scope_key', $scopeKey)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($idempotency instanceof CheckoutIdempotency) {
                $idempotencyExpiresAt = $idempotency->getRawOriginal('expires_at');

                if ($idempotencyExpiresAt !== null && now()->isAfter((string) $idempotencyExpiresAt)) {
                    $idempotency->update([
                        'cart_id' => $cart->id,
                        'order_id' => null,
                        'request_hash' => $hash,
                        'expires_at' => now()->addMinutes(30),
                    ]);
                } else {
                    if ($idempotency->request_hash !== $hash) {
                        throw new DomainException('Idempotency key reused with different payload.');
                    }

                    if ($idempotency->order_id !== null) {
                        /** @var Order $existingOrder */
                        $existingOrder = Order::query()->with(['items', 'payments', 'shipments'])->findOrFail($idempotency->order_id);

                        return $existingOrder;
                    }

                    if ($idempotency->cart_id !== null && $idempotency->cart_id !== $lockedCart->id) {
                        throw new DomainException('Idempotency key reused for a different cart.');
                    }
                }
            } else {
                $idempotency = CheckoutIdempotency::query()->create([
                    'scope_key' => $scopeKey,
                    'idempotency_key' => $idempotencyKey,
                    'cart_id' => $lockedCart->id,
                    'request_hash' => $hash,
                    'expires_at' => now()->addMinutes(30),
                ]);
            }

            if ($lockedCart->items->isEmpty()) {
                throw new DomainException('Cart is empty.');
            }

            if ((string) $lockedCart->getRawOriginal('status') !== CartStatus::ACTIVE->value) {
                throw new DomainException('Cart is not active for checkout.');
            }

            $subtotal = 0.0;
            $lineItems = [];

            foreach ($lockedCart->items as $item) {
                if (! $item instanceof CartItem) {
                    throw new DomainException('Cart item payload is invalid.');
                }

                $variant = $item->variant;

                if (! $variant instanceof ProductVariant) {
                    throw new DomainException('Cart contains unavailable items.');
                }

                $product = Product::query()
                    ->select(['id', 'status', 'published_at'])
                    ->whereKey($variant->product_id)
                    ->first();

                if (! $product instanceof Product || ! $variant->is_active
                    || (string) $product->getRawOriginal('status') !== ProductStatus::ACTIVE->value
                    || $product->published_at === null) {
                    throw new DomainException('Cart contains unavailable items.');
                }

                $inventory = Inventory::query()
                    ->where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory instanceof Inventory || $inventory->availableQuantity() < $item->quantity) {
                    throw new DomainException('Insufficient stock during checkout.');
                }

                $inventory->decrement('quantity', $item->quantity);
                $subtotal += (float) $item->line_total;

                $lineItems[] = [
                    'product_variant_id' => $item->product_variant_id,
                    'sku' => $variant->sku,
                    'name' => $variant->name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ];
            }

            $discountContext = $this->resolveDiscountContext($checkoutPayload, $subtotal);
            $discountTotal = $discountContext['discount_total'];
            $shippingTotal = 0.0;
            $total = $subtotal - $discountTotal + $shippingTotal;

            $order = Order::query()->create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'user_id' => $user?->id,
                'email' => (string) $checkoutPayload['email'],
                'status' => OrderStatus::PENDING->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'shipment_status' => ShipmentStatus::PENDING->value,
                'currency' => (string) ($checkoutPayload['currency'] ?? 'USD'),
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_total' => $shippingTotal,
                'total' => $total,
                'billing_address' => (array) ($checkoutPayload['billing_address'] ?? []),
                'shipping_address' => (array) ($checkoutPayload['shipping_address'] ?? []),
                'cart_snapshot' => $lineItems,
                'placed_at' => now(),
            ]);

            foreach ($lineItems as $lineItem) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_variant_id' => $lineItem['product_variant_id'],
                    'sku' => $lineItem['sku'],
                    'name' => $lineItem['name'],
                    'quantity' => $lineItem['quantity'],
                    'unit_price' => $lineItem['unit_price'],
                    'total_price' => $lineItem['line_total'],
                    'meta' => ['source_cart' => $lockedCart->id],
                ]);
            }

            $lockedCart->update(['status' => CartStatus::CHECKED_OUT->value]);

            if ($discountContext['coupon'] instanceof Coupon) {
                $discountContext['coupon']->increment('redeemed_count');
            }

            if ($discountContext['promotion'] instanceof Promotion) {
                $discountContext['promotion']->increment('usage_count');
            }

            $idempotency->update([
                'cart_id' => $lockedCart->id,
                'request_hash' => $hash,
                'order_id' => $order->id,
                'expires_at' => now()->addHours(24),
            ]);

            event(new OrderPlaced($order));

            return $order->fresh(['items', 'payments', 'shipments']);
        });
    }

    /**
     * Build checkout idempotency scope key.
     */
    private function resolveScopeKey(Cart $cart, ?User $user): string
    {
        if ($user !== null) {
            return 'user:'.$user->id;
        }

        if (! empty($cart->guest_token)) {
            return 'guest:'.$cart->guest_token;
        }

        throw new DomainException('Guest checkout requires guest token.');
    }

    /**
     * Resolve discount context from coupon code.
     *
     * @param  array<string, mixed>  $checkoutPayload
     * @return array{discount_total:float,coupon:?Coupon,promotion:?Promotion}
     */
    private function resolveDiscountContext(array $checkoutPayload, float $subtotal): array
    {
        $couponCode = strtoupper(trim((string) ($checkoutPayload['coupon_code'] ?? '')));

        if ($couponCode === '') {
            return [
                'discount_total' => 0.0,
                'coupon' => null,
                'promotion' => null,
            ];
        }

        $coupon = Coupon::query()
            ->where('code', $couponCode)
            ->lockForUpdate()
            ->first();

        if (! $coupon instanceof Coupon || ! $coupon->is_active) {
            throw new DomainException('Coupon code is invalid.');
        }

        $couponExpiresAt = $coupon->getRawOriginal('expires_at');

        if ($couponExpiresAt !== null && now()->isAfter((string) $couponExpiresAt)) {
            throw new DomainException('Coupon has expired.');
        }

        if ($coupon->max_redemptions !== null && $coupon->redeemed_count >= $coupon->max_redemptions) {
            throw new DomainException('Coupon usage limit exceeded.');
        }

        $promotion = Promotion::query()
            ->whereKey($coupon->promotion_id)
            ->lockForUpdate()
            ->first();

        if (! $promotion instanceof Promotion || ! $promotion->is_active) {
            throw new DomainException('Promotion is not available.');
        }

        $promotionStartsAt = $promotion->getRawOriginal('starts_at');
        if ($promotionStartsAt !== null && now()->isBefore((string) $promotionStartsAt)) {
            throw new DomainException('Promotion has not started yet.');
        }

        $promotionEndsAt = $promotion->getRawOriginal('ends_at');
        if ($promotionEndsAt !== null && now()->isAfter((string) $promotionEndsAt)) {
            throw new DomainException('Promotion has ended.');
        }

        if ($promotion->usage_limit !== null && $promotion->usage_count >= $promotion->usage_limit) {
            throw new DomainException('Promotion usage limit exceeded.');
        }

        $discountTotal = $this->calculateDiscountTotal($promotion->type, (float) $promotion->value, $subtotal);

        return [
            'discount_total' => $discountTotal,
            'coupon' => $coupon,
            'promotion' => $promotion,
        ];
    }

    /**
     * Calculate discount amount by promotion type.
     */
    private function calculateDiscountTotal(PromotionType|string $type, float $promotionValue, float $subtotal): float
    {
        try {
            $promotionType = $type instanceof PromotionType ? $type : PromotionType::from($type);
        } catch (\ValueError $exception) {
            throw new DomainException('Promotion type is invalid.', 0, $exception);
        }

        $discount = match ($promotionType) {
            PromotionType::PERCENT => min($subtotal * ($promotionValue / 100), $subtotal),
            PromotionType::FIXED => min($promotionValue, $subtotal),
        };

        return round(max(0.0, $discount), 2);
    }
}
