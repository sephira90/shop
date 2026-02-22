<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CheckoutIdempotency;
use App\Models\WebhookReceipt;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppMaintenanceCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure maintenance cleanup command prunes stale lifecycle records.
     */
    public function test_maintenance_cleanup_prunes_stale_records(): void
    {
        $staleIdempotency = CheckoutIdempotency::query()->create([
            'scope_key' => 'scope:stale',
            'idempotency_key' => 'stale-key',
            'cart_id' => null,
            'order_id' => null,
            'request_hash' => hash('sha256', 'stale'),
            'expires_at' => now()->subHours(72),
        ]);
        $freshIdempotency = CheckoutIdempotency::query()->create([
            'scope_key' => 'scope:fresh',
            'idempotency_key' => 'fresh-key',
            'cart_id' => null,
            'order_id' => null,
            'request_hash' => hash('sha256', 'fresh'),
            'expires_at' => now()->subHours(2),
        ]);

        $staleProcessedReceipt = WebhookReceipt::query()->create([
            'provider' => 'payment',
            'event_id' => 'evt-stale-processed',
            'payload_hash' => hash('sha256', 'payload-stale-processed'),
            'processed_at' => now()->subHours(72),
        ]);
        $freshProcessedReceipt = WebhookReceipt::query()->create([
            'provider' => 'payment',
            'event_id' => 'evt-fresh-processed',
            'payload_hash' => hash('sha256', 'payload-fresh-processed'),
            'processed_at' => now()->subHours(2),
        ]);
        $stalePendingReceipt = WebhookReceipt::query()->create([
            'provider' => 'shipping',
            'event_id' => 'evt-stale-pending',
            'payload_hash' => hash('sha256', 'payload-stale-pending'),
            'processed_at' => null,
        ]);
        WebhookReceipt::query()->whereKey($stalePendingReceipt->id)->update([
            'created_at' => now()->subHours(80),
            'updated_at' => now()->subHours(80),
        ]);
        $freshPendingReceipt = WebhookReceipt::query()->create([
            'provider' => 'shipping',
            'event_id' => 'evt-fresh-pending',
            'payload_hash' => hash('sha256', 'payload-fresh-pending'),
            'processed_at' => null,
        ]);

        $staleActiveCart = $this->createCart(CartStatus::ACTIVE, 'guest-active-stale');
        $this->touchCart($staleActiveCart, 80);
        $freshActiveCart = $this->createCart(CartStatus::ACTIVE, 'guest-active-fresh');
        $this->touchCart($freshActiveCart, 2);
        $staleInactiveCart = $this->createCart(CartStatus::CHECKED_OUT, null);
        $this->touchCart($staleInactiveCart, 80);
        $freshInactiveCart = $this->createCart(CartStatus::ABANDONED, null);
        $this->touchCart($freshInactiveCart, 2);

        $this->artisan(
            'app:maintenance-cleanup --idempotency-retain-hours=24 --webhook-retain-hours=24 --active-cart-retain-hours=24 --inactive-cart-retain-hours=24',
        )
            ->assertSuccessful()
            ->expectsOutputToContain('Maintenance cleanup completed.');

        $this->assertDatabaseMissing('checkout_idempotencies', ['id' => $staleIdempotency->id]);
        $this->assertDatabaseHas('checkout_idempotencies', ['id' => $freshIdempotency->id]);

        $this->assertDatabaseMissing('webhook_receipts', ['id' => $staleProcessedReceipt->id]);
        $this->assertDatabaseMissing('webhook_receipts', ['id' => $stalePendingReceipt->id]);
        $this->assertDatabaseHas('webhook_receipts', ['id' => $freshProcessedReceipt->id]);
        $this->assertDatabaseHas('webhook_receipts', ['id' => $freshPendingReceipt->id]);

        $this->assertDatabaseMissing('carts', ['id' => $staleActiveCart->id]);
        $this->assertDatabaseMissing('carts', ['id' => $staleInactiveCart->id]);
        $this->assertDatabaseHas('carts', ['id' => $freshActiveCart->id]);
        $this->assertDatabaseHas('carts', ['id' => $freshInactiveCart->id]);
    }

    /**
     * Ensure dry-run mode reports but does not delete records.
     */
    public function test_maintenance_cleanup_dry_run_does_not_delete_records(): void
    {
        $idempotency = CheckoutIdempotency::query()->create([
            'scope_key' => 'scope:dry-run',
            'idempotency_key' => 'dry-run-key',
            'cart_id' => null,
            'order_id' => null,
            'request_hash' => hash('sha256', 'dry-run'),
            'expires_at' => now()->subHours(72),
        ]);
        $receipt = WebhookReceipt::query()->create([
            'provider' => 'payment',
            'event_id' => 'evt-dry-run',
            'payload_hash' => hash('sha256', 'evt-dry-run'),
            'processed_at' => now()->subHours(72),
        ]);
        $cart = $this->createCart(CartStatus::CHECKED_OUT, null);
        $this->touchCart($cart, 80);

        $this->artisan(
            'app:maintenance-cleanup --dry-run --idempotency-retain-hours=24 --webhook-retain-hours=24 --active-cart-retain-hours=24 --inactive-cart-retain-hours=24',
        )
            ->assertSuccessful()
            ->expectsOutputToContain('Dry run: no records deleted.');

        $this->assertDatabaseHas('checkout_idempotencies', ['id' => $idempotency->id]);
        $this->assertDatabaseHas('webhook_receipts', ['id' => $receipt->id]);
        $this->assertDatabaseHas('carts', ['id' => $cart->id]);
    }

    /**
     * Ensure cleanup command is wired in scheduler.
     */
    public function test_maintenance_cleanup_command_is_registered_in_scheduler(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $hasCleanupEvent = collect($schedule->events())
            ->contains(static fn ($event): bool => str_contains((string) ($event->command ?? ''), 'app:maintenance-cleanup'));

        $this->assertTrue($hasCleanupEvent);
    }

    /**
     * Create cart with status and optional guest token.
     */
    private function createCart(CartStatus $status, ?string $guestToken): Cart
    {
        return Cart::query()->create([
            'guest_token' => $guestToken ?? 'guest-'.Str::lower(Str::random(12)),
            'currency' => 'USD',
            'status' => $status->value,
        ]);
    }

    /**
     * Shift cart timestamps back by given hours.
     */
    private function touchCart(Cart $cart, int $hoursAgo): void
    {
        Cart::query()->whereKey($cart->id)->update([
            'created_at' => now()->subHours($hoursAgo),
            'updated_at' => now()->subHours($hoursAgo),
        ]);
    }
}
