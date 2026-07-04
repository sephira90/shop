<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesCatalogVariant;
use Tests\Support\OpenApi\AssertsOpenApiResponse;
use Tests\Support\OpenApi\SpecAssertionHelper;
use Tests\TestCase;

/**
 * Conformance suite: every in-scope `/api/v1` endpoint in `docs/api/openapi.yaml`
 * must produce responses that match the spec. This is the executable enforcement
 * of the contract; spec drift fails this suite.
 *
 * Coverage strategy: the happy-path success response of each in-scope endpoint,
 * plus the canonical error shapes (422 validation, 401 unauth, 404 not_found).
 * Edge cases beyond these live in their dedicated feature tests; this suite
 * stays focused on contract shape, not domain behavior.
 */
final class OpenApiConformanceFeatureTest extends TestCase
{
    use AssertsOpenApiResponse;
    use CreatesCatalogVariant;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SpecAssertionHelper::resetCachedSpec();
        $this->seed(RoleSeeder::class);
    }

    public function test_register_response_conforms_to_spec(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane-conformance@example.com',
            'password' => 'verysecurepassword12',
            'password_confirmation' => 'verysecurepassword12',
        ]);

        $response->assertCreated();
        $this->assertResponseMatchesOpenApiSpec($response, 'POST', '/auth/register');
    }

    public function test_register_validation_error_conforms_to_spec(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => '',
            'last_name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertStatus(422);
        $this->assertResponseMatchesOpenApiSpec($response, 'POST', '/auth/register');
    }

    public function test_login_response_conforms_to_spec(): void
    {
        $user = User::factory()->create([
            'email' => 'login-conformance@example.com',
            'password' => bcrypt('verysecurepassword12'),
        ]);
        $user->assignRole('customer');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login-conformance@example.com',
            'password' => 'verysecurepassword12',
        ]);

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'POST', '/auth/login');
    }

    public function test_login_invalid_credentials_conforms_to_spec(): void
    {
        User::factory()->create(['email' => 'unknown-conformance@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown-conformance@example.com',
            'password' => 'wrong-password-12',
        ]);

        $response->assertStatus(422);
        $this->assertResponseMatchesOpenApiSpec($response, 'POST', '/auth/login');
    }

    public function test_logout_response_conforms_to_spec(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('browser')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'POST', '/auth/logout');
    }

    public function test_logout_unauthenticated_conforms_to_spec(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
        $this->assertResponseMatchesOpenApiSpec($response, 'POST', '/auth/logout');
    }

    public function test_me_response_conforms_to_spec(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $token = $user->createToken('browser')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'GET', '/auth/me');
    }

    public function test_me_unauthenticated_conforms_to_spec(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
        $this->assertResponseMatchesOpenApiSpec($response, 'GET', '/auth/me');
    }

    public function test_profile_update_response_conforms_to_spec(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $token = $user->createToken('browser')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/auth/profile', [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
            ]);

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'PATCH', '/auth/profile');
    }

    public function test_forgot_password_validation_error_conforms_to_spec(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $this->assertResponseMatchesOpenApiSpec($response, 'POST', '/auth/forgot-password');
    }

    public function test_verification_notification_response_conforms_to_spec(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $token = $user->createToken('browser')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/email/verification-notification');

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'POST', '/auth/email/verification-notification');
    }

    public function test_catalog_products_list_conforms_to_spec(): void
    {
        $this->createActiveVariantWithInventory();

        $response = $this->getJson('/api/v1/catalog/products');

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'GET', '/catalog/products');
    }

    public function test_catalog_products_show_conforms_to_spec(): void
    {
        $variant = $this->createActiveVariantWithInventory();
        $product = $variant->product()->firstOrFail();

        $response = $this->getJson("/api/v1/catalog/products/{$product->slug}");

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'GET', '/catalog/products/{slug}');
    }

    public function test_catalog_products_show_not_found_conforms_to_spec(): void
    {
        $response = $this->getJson('/api/v1/catalog/products/non-existent-slug-conformance');

        $response->assertNotFound();
        $this->assertResponseMatchesOpenApiSpec($response, 'GET', '/catalog/products/{slug}');
    }

    public function test_catalog_categories_list_conforms_to_spec(): void
    {
        $this->createActiveVariantWithInventory();

        $response = $this->getJson('/api/v1/catalog/categories');

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'GET', '/catalog/categories');
    }

    public function test_cart_show_conforms_to_spec(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/cart');

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'GET', '/cart');
    }

    public function test_cart_upsert_conforms_to_spec(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);
        $variant = $this->createActiveVariantWithInventory();

        $response = $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'POST', '/cart/items');
    }

    public function test_cart_remove_conforms_to_spec(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);
        $variant = $this->createActiveVariantWithInventory();

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk();

        $response = $this->deleteJson("/api/v1/cart/items/{$variant->id}");

        $response->assertOk();
        $this->assertResponseMatchesOpenApiSpec($response, 'DELETE', '/cart/items/{variantId}');
    }
}
