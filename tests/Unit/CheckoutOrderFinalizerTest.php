<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\ValueObjects\Money;
use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PromotionType;
use App\Enums\ShipmentStatus;
use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\CheckoutIdempotency;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Promotion;
use App\Services\Checkout\CheckoutOrderFinalizer;
use App\Services\Checkout\Dto\CheckoutDiscountContextDto;
use App\Services\Checkout\Dto\CheckoutOrderFinalizationInputDto;
use App\Support\Data\TypedValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckoutOrderFinalizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalize_marks_cart_updates_counters_completes_idempotency_and_dispatches_event(): void
    {
        Event::fake();

        $now = Carbon::parse('2026-02-28 12:00:00');
        Carbon::setTestNow($now);
        try {
            $promotion = Promotion::query()->create([
                'name' => 'Save ten percent',
                'code' => 'PROMO10',
                'type' => PromotionType::PERCENT->value,
                'value' => 10,
                'is_active' => true,
                'starts_at' => $now->copy()->subHour(),
                'ends_at' => $now->copy()->addHour(),
            ]);

            $coupon = Coupon::query()->create([
                'promotion_id' => $promotion->id,
                'code' => 'SAVE10',
                'is_active' => true,
            ]);

            $cart = Cart::query()->create([
                'guest_token' => 'checkout-finalizer-token',
                'currency' => 'USD',
                'status' => CartStatus::ACTIVE->value,
            ]);

            $order = Order::unguarded(fn (): Order => Order::query()->create([
                'order_number' => 'ORD-TEST-000001',
                'email' => 'guest@example.com',
                'status' => OrderStatus::PENDING->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'shipment_status' => ShipmentStatus::PENDING->value,
                'currency' => 'USD',
                'subtotal' => 100,
                'discount_total' => 10,
                'shipping_total' => 0,
                'total' => 90,
                'billing_address' => ['line1' => '1 Main Street'],
                'shipping_address' => ['line1' => '1 Main Street'],
                'cart_snapshot' => ['items' => []],
                'placed_at' => $now,
            ]));

            $idempotency = CheckoutIdempotency::query()->create([
                'scope_key' => 'guest:checkout-finalizer-token',
                'idempotency_key' => 'checkout-finalizer-key',
                'cart_id' => $cart->id,
                'request_hash' => str_repeat('a', 64),
                'expires_at' => $now->copy()->addMinutes(30),
            ]);

            $finalizedOrder = app(CheckoutOrderFinalizer::class)->finalize(new CheckoutOrderFinalizationInputDto(
                lockedCart: $cart,
                order: $order,
                idempotency: $idempotency,
                discountContext: new CheckoutDiscountContextDto(
                    discountTotal: Money::fromDecimal(10.0, 'USD'),
                    coupon: $coupon,
                    promotion: $promotion,
                ),
                requestHash: str_repeat('b', 64),
            ));

            $freshCart = $cart->fresh();
            $freshCoupon = $coupon->fresh();
            $freshPromotion = $promotion->fresh();
            $freshIdempotency = CheckoutIdempotency::query()->findOrFail($idempotency->id);

            $this->assertInstanceOf(Cart::class, $freshCart);
            $this->assertInstanceOf(Coupon::class, $freshCoupon);
            $this->assertInstanceOf(Promotion::class, $freshPromotion);
            $this->assertSame(CartStatus::CHECKED_OUT, $freshCart->status);
            $this->assertSame(1, $freshCoupon->redeemed_count);
            $this->assertSame(1, $freshPromotion->usage_count);
            $this->assertSame($order->id, $freshIdempotency->order_id);
            $this->assertSame(str_repeat('b', 64), $freshIdempotency->request_hash);
            $this->assertSame(
                $now->copy()->addHours(24)->toDateTimeString(),
                Carbon::parse(TypedValue::string($freshIdempotency->getRawOriginal('expires_at')))->toDateTimeString(),
            );
            $this->assertSame($order->id, $finalizedOrder->id);
            $this->assertTrue($finalizedOrder->relationLoaded('items'));
            $this->assertTrue($finalizedOrder->relationLoaded('payments'));
            $this->assertTrue($finalizedOrder->relationLoaded('shipments'));

            Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event): bool => $event->order->is($order));
        } finally {
            Carbon::setTestNow();
        }
    }
}
