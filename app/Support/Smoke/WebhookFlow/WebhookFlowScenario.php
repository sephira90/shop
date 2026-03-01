<?php

declare(strict_types=1);

namespace App\Support\Smoke\WebhookFlow;

use App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto;
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
use App\Support\Data\JsonPayload;
use App\Support\Data\TypedValue;
use App\Support\Smoke\WebhookFlow\Dto\WebhookFlowSmokeResultDto;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class WebhookFlowScenario
{
    /**
     * Create webhook flow smoke scenario.
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly PaymentService $paymentService,
        private readonly ShippingService $shippingService,
    ) {}

    /**
     * Execute checkout and webhook chain.
     */
    public function run(): WebhookFlowSmokeResultDto
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
        $transactionId = $payment->transaction_id;

        if (! is_string($transactionId) || $transactionId === '') {
            throw new DomainException('Payment transaction id is missing after payment initiation.');
        }

        $this->processPaymentWebhook($transactionId);

        $freshOrder = Order::query()->findOrFail($order->id);
        $freshPayment = Payment::query()->findOrFail($payment->id);

        if (TypedValue::string($freshPayment->getRawOriginal('status')) !== PaymentStatus::CAPTURED->value) {
            throw new DomainException('Payment webhook did not capture payment.');
        }

        if (TypedValue::string($freshOrder->getRawOriginal('status')) !== OrderStatus::PAID->value) {
            throw new DomainException('Order status did not transition to paid.');
        }

        $shipment = Shipment::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        if (! ($shipment instanceof Shipment)) {
            // Payment webhook side-effects are dispatched after commit. In production rollback
            // mode this command keeps an outer transaction open, so run shipment sync fallback.
            DispatchShipmentJob::dispatchSync($order->id);

            $shipment = Shipment::query()
                ->where('order_id', $order->id)
                ->latest('id')
                ->first();
        }

        if (! ($shipment instanceof Shipment)) {
            throw new DomainException('Shipment was not created after captured payment.');
        }

        $trackingNumber = $shipment->tracking_number;

        if (! is_string($trackingNumber) || $trackingNumber === '') {
            throw new DomainException('Shipment tracking number is missing after shipment creation.');
        }

        $this->processShippingWebhook($trackingNumber);

        $completedOrder = Order::query()->findOrFail($order->id);
        $deliveredShipment = Shipment::query()->findOrFail($shipment->id);

        if (TypedValue::string($completedOrder->getRawOriginal('status')) !== OrderStatus::COMPLETED->value) {
            throw new DomainException('Order status did not transition to completed.');
        }

        if (TypedValue::string($completedOrder->getRawOriginal('shipment_status')) !== ShipmentStatus::DELIVERED->value) {
            throw new DomainException('Order shipment status did not transition to delivered.');
        }

        if (TypedValue::string($deliveredShipment->getRawOriginal('status')) !== ShipmentStatus::DELIVERED->value) {
            throw new DomainException('Shipment status did not transition to delivered.');
        }

        return new WebhookFlowSmokeResultDto(
            orderId: $completedOrder->id,
            paymentId: $freshPayment->id,
            shipmentId: $deliveredShipment->id,
            orderStatus: TypedValue::string($completedOrder->getRawOriginal('status')),
            paymentStatus: TypedValue::string($freshPayment->getRawOriginal('status')),
            shipmentStatus: TypedValue::string($deliveredShipment->getRawOriginal('status')),
        );
    }

    /**
     * Ensure at least one active catalog variant exists.
     */
    private function ensureCatalogVariant(): ProductVariant
    {
        app(RoleSeeder::class)->run();

        $variant = $this->findActiveVariant();

        if (! ($variant instanceof ProductVariant)) {
            app(CatalogSeeder::class)->run();
            $variant = $this->findActiveVariant();
        }

        if (! ($variant instanceof ProductVariant)) {
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

        if (! ($user instanceof User)) {
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
            ->whereHas('inventory', static function ($query): void {
                $query->whereColumn('quantity', '>', 'reserved_quantity');
            })
            ->orderBy('id')
            ->first();
    }

    /**
     * Build checkout payload for smoke order.
     */
    private function buildCheckoutPayload(User $user): CheckoutPlaceOrderInputDto
    {
        return CheckoutPlaceOrderInputDto::fromValidated([
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
        ]);
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

        $payloadDto = JsonPayload::fromArray($payload);
        $this->paymentService->processWebhook($payloadDto, $signature, source: 'smoke');
        $this->paymentService->processWebhook($payloadDto, $signature, source: 'smoke');
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

        $payloadDto = JsonPayload::fromArray($payload);
        $this->shippingService->processWebhook($payloadDto, $signature, source: 'smoke');
        $this->shippingService->processWebhook($payloadDto, $signature, source: 'smoke');
    }
}
