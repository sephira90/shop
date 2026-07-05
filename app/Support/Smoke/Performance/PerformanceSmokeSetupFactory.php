<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance;

use App\Application\Admin\Orders\Dto\AdminOrderListFilterDto;
use App\Application\Admin\Products\Dto\AdminProductListFilterDto;
use App\Domains\Cart\Contracts\CartServiceInterface;
use App\Domains\Catalog\Contracts\Dto\CatalogProductListFilterDto;
use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeContextDto;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Support\Str;

final class PerformanceSmokeSetupFactory
{
    /**
     * @var list<string>
     */
    private const CATALOG_SCENARIOS = [
        'catalog_list_cold',
        'catalog_list_warm',
        'cart_show',
        'checkout_place_order',
        'admin_products_list',
    ];

    /**
     * @var list<string>
     */
    private const CART_SCENARIOS = [
        'cart_show',
        'checkout_place_order',
    ];

    public function __construct(
        private readonly CartServiceInterface $cartService,
    ) {}

    /**
     * @param  list<string>  $selectedScenarios
     */
    public function build(array $selectedScenarios): PerformanceSmokeContextDto
    {
        $catalogFilter = CatalogProductListFilterDto::fromValidated([
            'q' => '__smoke_cache_buster_'.Str::uuid()->toString(),
        ]);
        $cartGuestToken = 'perf-cart-'.Str::lower(Str::random(12));
        $checkoutGuestToken = 'perf-checkout-'.Str::lower(Str::random(12));
        $checkoutPayload = CheckoutPlaceOrderInputDto::fromValidated([
            'email' => 'performance-smoke-'.Str::lower(Str::random(8)).'@shop.local',
            'billing_address' => [
                'line1' => '1 Performance Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
            'shipping_address' => [
                'line1' => '1 Performance Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
        ]);
        $orderFilter = AdminOrderListFilterDto::fromValidated([
            'page' => 1,
            'per_page' => 20,
        ]);
        $productFilter = AdminProductListFilterDto::fromValidated([
            'page' => 1,
            'per_page' => 20,
        ]);

        if ($this->requiresCatalogData($selectedScenarios)) {
            $this->seedCatalogData();
        }

        if ($this->requiresPreparedCarts($selectedScenarios)) {
            $variantId = $this->ensureAvailableVariantId();
            $this->prepareGuestCart($cartGuestToken, $variantId);
            $this->prepareGuestCart($checkoutGuestToken, $variantId);
        }

        if (in_array('admin_orders_summary', $selectedScenarios, true)) {
            $this->seedAdminOrdersFixture();
        }

        return new PerformanceSmokeContextDto(
            catalogFilter: $catalogFilter,
            cartGuestToken: $cartGuestToken,
            checkoutGuestToken: $checkoutGuestToken,
            checkoutPayload: $checkoutPayload,
            orderFilter: $orderFilter,
            productFilter: $productFilter,
        );
    }

    /**
     * @param  list<string>  $selectedScenarios
     */
    private function requiresCatalogData(array $selectedScenarios): bool
    {
        return array_intersect(self::CATALOG_SCENARIOS, $selectedScenarios) !== [];
    }

    /**
     * @param  list<string>  $selectedScenarios
     */
    private function requiresPreparedCarts(array $selectedScenarios): bool
    {
        return array_intersect(self::CART_SCENARIOS, $selectedScenarios) !== [];
    }

    private function seedCatalogData(): void
    {
        app(RoleSeeder::class)->run();
        app(CatalogSeeder::class)->run();
    }

    private function ensureAvailableVariantId(): int
    {
        $variantId = $this->findAvailableVariantId();

        if ($variantId === null) {
            $this->seedCatalogData();
            $variantId = $this->findAvailableVariantId();
        }

        if ($variantId === null) {
            throw new DomainException('Performance smoke precondition failed: no available variant found.');
        }

        return $variantId;
    }

    private function findAvailableVariantId(): ?int
    {
        $variantId = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', static function ($productQuery): void {
                $productQuery
                    ->where('status', ProductStatus::ACTIVE->value)
                    ->whereNotNull('published_at');
            })
            ->whereHas('inventory', static function ($inventoryQuery): void {
                $inventoryQuery->whereColumn('quantity', '>', 'reserved_quantity');
            })
            ->orderBy('id')
            ->value('id');

        return is_numeric($variantId) ? (int) $variantId : null;
    }

    private function prepareGuestCart(string $guestToken, int $variantId): void
    {
        $cart = $this->cartService->resolve(null, $guestToken);
        $this->cartService->upsertItem($cart, $variantId, 1);
    }

    private function seedAdminOrdersFixture(): void
    {
        $seedPrefix = 'PERF-'.Str::upper(Str::random(6));

        foreach (range(1, 25) as $index) {
            Order::unguarded(fn (): Order => Order::query()->create([
                'order_number' => sprintf('ORD-%s-%04d', $seedPrefix, $index),
                'email' => "perf{$index}@example.com",
                'status' => 'pending',
                'payment_status' => 'pending',
                'shipment_status' => 'pending',
                'currency' => 'USD',
                'subtotal' => 100 + $index,
                'discount_total' => 0,
                'shipping_total' => 0,
                'total' => 100 + $index,
                'billing_address' => ['line1' => 'Smoke Street'],
                'shipping_address' => ['line1' => 'Smoke Street'],
                'cart_snapshot' => [],
                'placed_at' => now(),
            ]));
        }
    }
}
