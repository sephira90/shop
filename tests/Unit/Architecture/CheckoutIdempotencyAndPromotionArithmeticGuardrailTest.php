<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * R2 guardrail: exact-decimal promotion arithmetic and configurable
 * idempotency retention windows are part of the architectural contract and
 * must not regress silently.
 *
 * 1. CheckoutDiscountResolver consumes the promotion value as an exact
 *    decimal string and never casts it to float, and its private discount
 *    calculation method is statically typed (no PromotionType|string union,
 *    no float promotionValue).
 * 2. CheckoutIdempotencyGuard and CheckoutOrderFinalizer resolve both
 *    retention windows through config keys instead of hardcoded literals.
 * 3. config/checkout.php declares the bounded positive-integer resolver for
 *    both windows so a misconfiguration fails fast at boot instead of
 *    producing silent retention drift.
 * 4. The documented env keys resolve to the declared defaults.
 */
final class CheckoutIdempotencyAndPromotionArithmeticGuardrailTest extends TestCase
{
    public function test_checkout_discount_resolver_never_casts_promotion_value_to_float(): void
    {
        $source = File::get(app_path('Domains/Checkout/Services/CheckoutDiscountResolver.php'));

        $this->assertStringNotContainsString(
            '(float) $promotion->value',
            $source,
            'CheckoutDiscountResolver must consume the promotion value as an exact decimal string, not as float.',
        );

        $this->assertStringNotContainsString(
            'PromotionType|string $type',
            $source,
            'CheckoutDiscountResolver discount calculation must be statically typed; the PromotionType|string union is closed.',
        );

        $this->assertStringNotContainsString(
            'float $promotionValue',
            $source,
            'CheckoutDiscountResolver discount calculation must accept a decimal string, not float.',
        );

        $this->assertStringContainsString(
            'calculateDiscountTotal(PromotionType $type, string $promotionValue',
            $source,
            'CheckoutDiscountResolver discount calculation must declare PromotionType and string parameters.',
        );
    }

    public function test_checkout_discount_resolver_applies_percent_discount_through_money_string_rate(): void
    {
        $source = File::get(app_path('Domains/Checkout/Services/CheckoutDiscountResolver.php'));

        $this->assertStringContainsString(
            '->percentage($',
            $source,
            'CheckoutDiscountResolver must apply percent discounts through Money::percentage() with an exact rate argument.',
        );
    }

    public function test_idempotency_guard_resolves_pending_window_through_config(): void
    {
        $source = File::get(app_path('Domains/Checkout/Services/CheckoutIdempotencyGuard.php'));

        $this->assertStringNotContainsString(
            'addMinutes(30)',
            $source,
            'CheckoutIdempotencyGuard must resolve the pending window through config, not a hardcoded literal.',
        );

        $this->assertStringContainsString(
            "config('checkout.idempotency.pending_minutes')",
            $source,
            'CheckoutIdempotencyGuard must read the pending window from the checkout idempotency config.',
        );
    }

    public function test_checkout_order_finalizer_resolves_completed_window_through_config(): void
    {
        $source = File::get(app_path('Domains/Checkout/Services/CheckoutOrderFinalizer.php'));

        $this->assertStringNotContainsString(
            'addHours(24)',
            $source,
            'CheckoutOrderFinalizer must resolve the completed replay window through config, not a hardcoded literal.',
        );

        $this->assertStringContainsString(
            "config('checkout.idempotency.completed_hours')",
            $source,
            'CheckoutOrderFinalizer must read the completed replay window from the checkout idempotency config.',
        );
    }

    public function test_checkout_config_declares_bounded_resolver_and_documented_defaults(): void
    {
        $this->assertFileExists(config_path('checkout.php'), 'Checkout configuration file must exist.');

        $source = File::get(config_path('checkout.php'));

        $this->assertStringContainsString(
            "envKey: 'CHECKOUT_IDEMPOTENCY_PENDING_MINUTES'",
            $source,
            'config/checkout.php must declare the pending window env key.',
        );

        $this->assertStringContainsString(
            "envKey: 'CHECKOUT_IDEMPOTENCY_COMPLETED_HOURS'",
            $source,
            'config/checkout.php must declare the completed window env key.',
        );

        $this->assertStringContainsString(
            'FILTER_VALIDATE_INT',
            $source,
            'config/checkout.php must validate idempotency windows as bounded positive integers.',
        );

        $this->assertStringContainsString(
            'default: 30,',
            $source,
            'Pending window must default to 30 minutes.',
        );

        $this->assertStringContainsString(
            'default: 24,',
            $source,
            'Completed window must default to 24 hours.',
        );
    }

    public function test_checkout_config_resolves_documented_defaults(): void
    {
        $this->assertSame(30, config('checkout.idempotency.pending_minutes'));
        $this->assertSame(24, config('checkout.idempotency.completed_hours'));
    }
}
