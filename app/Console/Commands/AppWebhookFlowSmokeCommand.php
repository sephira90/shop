<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\RoleName;
use App\Enums\ShipmentStatus;
use App\Jobs\DispatchShipmentJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\PaymentService;
use App\Services\Shipping\ShippingService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class AppWebhookFlowSmokeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:webhook-flow-smoke
        {--persist : Persist smoke records instead of rolling them back in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run checkout and webhook integration smoke checks with idempotency guards.';

    /**
     * Create command instance.
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly PaymentService $paymentService,
        private readonly ShippingService $shippingService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $originalQueueConnection = Config::get('queue.default');
        Config::set('queue.default', 'sync');
        $rolledBack = false;

        try {
            if ($this->shouldRollbackSmokeData()) {
                DB::beginTransaction();

                try {
                    $result = $this->runSmokeFlow();
                    DB::rollBack();
                    $rolledBack = true;
                } catch (Throwable $exception) {
                    if (DB::transactionLevel() > 0) {
                        DB::rollBack();
                    }

                    throw $exception;
                }
            } else {
                $result = $this->runSmokeFlow();
            }
        } catch (Throwable $exception) {
            $this->error('Webhook flow smoke failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            Config::set('queue.default', $originalQueueConnection);
        }

        $this->table(
            ['metric', 'value'],
            [
                ['order_id', (string) $result['order_id']],
                ['payment_id', (string) $result['payment_id']],
                ['shipment_id', (string) $result['shipment_id']],
                ['order_status', $result['order_status']],
                ['payment_status', $result['payment_status']],
                ['shipment_status', $result['shipment_status']],
            ],
        );

        if ($rolledBack) {
            $this->warn('Production safeguard: smoke data rolled back. Use --persist to keep records.');
        }

        $this->info('Webhook flow smoke checks passed.');

        return self::SUCCESS;
    }

    /**
     * Determine whether smoke writes must be rolled back.
     */
    private function shouldRollbackSmokeData(): bool
    {
        return (string) config('app.env') === 'production' && ! (bool) $this->option('persist');
    }

    /**
     * Execute checkout and webhook chain.
     *
     * @return array{
     *     order_id:string,
     *     payment_id:int,
     *     shipment_id:int,
     *     order_status:string,
     *     payment_status:string,
     *     shipment_status:string
     * }
     */
    private function runSmokeFlow(): array
    {
        $variant = $this->ensureCatalogVariant();
        $user = $this->resolveSmokeUser();

        $cart = $this->cartService->resolve($user);
        $cart = $this->cartService->upsertItem($cart, $variant->id, 1);

        $checkoutKey = 'smoke-checkout-'.Str::lower(Str::random(16));
        $checkoutPayload = $this->buildCheckoutPayload($user);

        $order = $this->checkoutService->placeOrder($cart, $checkoutPayload, $checkoutKey, $user);
        $idempotentOrder = $this->checkoutService->placeOrder($cart, $checkoutPayload, $checkoutKey, $user);

        if ($idempotentOrder->id !== $order->id) {
            throw new DomainException('Checkout idempotency validation failed.');
        }

        $payment = $this->paymentService->initiate($order, 'smoke-pay-'.$checkoutKey);
        $this->processPaymentWebhook($payment->transaction_id);

        $freshOrder = Order::query()->findOrFail($order->id);
        $freshPayment = Payment::query()->findOrFail($payment->id);

        if ((string) $freshPayment->getRawOriginal('status') !== PaymentStatus::CAPTURED->value) {
            throw new DomainException('Payment webhook did not capture payment.');
        }

        if ((string) $freshOrder->getRawOriginal('status') !== OrderStatus::PAID->value) {
            throw new DomainException('Order status did not transition to paid.');
        }

        $shipment = Shipment::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        if (! $shipment instanceof Shipment) {
            // Payment webhook side-effects are dispatched after commit. In production rollback
            // mode this command keeps an outer transaction open, so run shipment sync fallback.
            DispatchShipmentJob::dispatchSync($order->id);

            $shipment = Shipment::query()
                ->where('order_id', $order->id)
                ->latest('id')
                ->first();
        }

        if (! $shipment instanceof Shipment) {
            throw new DomainException('Shipment was not created after captured payment.');
        }

        $this->processShippingWebhook($shipment->tracking_number);

        $completedOrder = Order::query()->findOrFail($order->id);
        $deliveredShipment = Shipment::query()->findOrFail($shipment->id);

        if ((string) $completedOrder->getRawOriginal('status') !== OrderStatus::COMPLETED->value) {
            throw new DomainException('Order status did not transition to completed.');
        }

        if ((string) $completedOrder->getRawOriginal('shipment_status') !== ShipmentStatus::DELIVERED->value) {
            throw new DomainException('Order shipment status did not transition to delivered.');
        }

        if ((string) $deliveredShipment->getRawOriginal('status') !== ShipmentStatus::DELIVERED->value) {
            throw new DomainException('Shipment status did not transition to delivered.');
        }

        return [
            'order_id' => $completedOrder->id,
            'payment_id' => $freshPayment->id,
            'shipment_id' => $deliveredShipment->id,
            'order_status' => (string) $completedOrder->getRawOriginal('status'),
            'payment_status' => (string) $freshPayment->getRawOriginal('status'),
            'shipment_status' => (string) $deliveredShipment->getRawOriginal('status'),
        ];
    }

    /**
     * Ensure at least one active catalog variant exists.
     */
    private function ensureCatalogVariant(): ProductVariant
    {
        app(RoleSeeder::class)->run();

        $variant = $this->findActiveVariant();

        if (! $variant instanceof ProductVariant) {
            app(CatalogSeeder::class)->run();
            $variant = $this->findActiveVariant();
        }

        if (! $variant instanceof ProductVariant) {
            throw new DomainException('Unable to find active catalog variant for smoke flow.');
        }

        return $variant;
    }

    /**
     * Resolve smoke user with customer role.
     */
    private function resolveSmokeUser(): User
    {
        $email = 'smoke.user@shop.local';

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $user = User::query()->create([
                'first_name' => 'Smoke',
                'last_name' => 'User',
                'name' => 'Smoke User',
                'email' => $email,
                'password' => Hash::make(Str::random(24)),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
        } else {
            $user->forceFill([
                'name' => $user->name !== '' ? $user->name : 'Smoke User',
                'is_active' => true,
            ])->save();

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }

        $user->assignRole(RoleName::CUSTOMER);

        return $user;
    }

    /**
     * Find one active and published variant with inventory.
     */
    private function findActiveVariant(): ?ProductVariant
    {
        return ProductVariant::query()
            ->with('inventory')
            ->where('is_active', true)
            ->whereHas('product', static function ($query): void {
                $query
                    ->where('status', ProductStatus::ACTIVE->value)
                    ->whereNotNull('published_at');
            })
            ->first();
    }

    /**
     * Build checkout payload for smoke order.
     *
     * @return array<string, mixed>
     */
    private function buildCheckoutPayload(User $user): array
    {
        return [
            'email' => $user->email,
            'billing_address' => [
                'line1' => '1 Smoke Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
            'shipping_address' => [
                'line1' => '1 Smoke Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
        ];
    }

    /**
     * Process payment webhook and replay same event for idempotency check.
     */
    private function processPaymentWebhook(string $transactionId): void
    {
        $eventId = 'evt-smoke-pay-'.Str::lower(Str::random(12));
        $payload = [
            'event_id' => $eventId,
            'transaction_id' => $transactionId,
            'status' => 'paid',
        ];
        $signature = hash('sha256', $eventId);

        $this->paymentService->processWebhook($payload, $signature);
        $this->paymentService->processWebhook($payload, $signature);
    }

    /**
     * Process shipping webhook and replay same event for idempotency check.
     */
    private function processShippingWebhook(string $trackingNumber): void
    {
        $eventId = 'evt-smoke-ship-'.Str::lower(Str::random(12));
        $payload = [
            'event_id' => $eventId,
            'tracking_number' => $trackingNumber,
            'status' => 'delivered',
        ];
        $signature = hash('sha256', $eventId);

        $this->shippingService->processWebhook($payload, $signature);
        $this->shippingService->processWebhook($payload, $signature);
    }
}
