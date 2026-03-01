<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ApiContractSmokeCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure API contract smoke command passes for key endpoints.
     */
    public function test_api_contract_smoke_command_passes(): void
    {
        $this->artisanCommand('app:api-contract-smoke')
            ->expectsOutputToContain('shipping_webhook_missing_signature')
            ->assertSuccessful()
            ->expectsOutputToContain('API contract smoke checks passed.');
    }

    /**
     * Ensure production execution does not persist smoke data by default.
     */
    public function test_api_contract_smoke_command_rolls_back_data_in_production(): void
    {
        config()->set('app.env', 'production');

        $this->artisanCommand('app:api-contract-smoke')
            ->assertSuccessful()
            ->expectsOutputToContain('Production safeguard: smoke data rolled back.');

        $this->assertDatabaseMissing('users', ['email' => 'api.contract.manager@shop.local']);
        $this->assertDatabaseCount('products', 0);
    }

    /**
     * Ensure checkout smoke setup ignores unavailable variants created before catalog seed.
     */
    public function test_api_contract_smoke_command_uses_available_variant_for_checkout_setup(): void
    {
        $category = Category::query()->create([
            'name' => 'Smoke Category',
            'slug' => 'smoke-category',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'sku' => 'SMOKE-DRAFT-001',
            'name' => 'Smoke Draft Product',
            'slug' => 'smoke-draft-product',
            'status' => ProductStatus::DRAFT->value,
            'category_id' => $category->id,
            'published_at' => null,
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SMOKE-DRAFT-001-V1',
            'name' => 'Unavailable Variant',
            'price' => 10,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->artisanCommand('app:api-contract-smoke')
            ->assertSuccessful()
            ->expectsOutputToContain('API contract smoke checks passed.');
    }

    public function test_api_contract_smoke_command_can_run_selected_scenario_subset(): void
    {
        $exitCode = Artisan::call('app:api-contract-smoke', [
            '--only' => 'shipping_webhook',
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('shipping_webhook_missing_signature', $output);
        $this->assertStringNotContainsString('catalog_products_list', $output);
    }

    public function test_api_contract_smoke_command_fails_for_unknown_selected_scenario(): void
    {
        $this->artisanCommand('app:api-contract-smoke --only=missing_scenario')
            ->assertFailed()
            ->expectsOutputToContain('API contract smoke failed: Option --only contains unknown api smoke scenario "missing_scenario".');
    }
}
