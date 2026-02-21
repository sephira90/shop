<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCacheRefreshTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure manager can refresh catalog cache version.
     */
    public function test_manager_can_refresh_catalog_cache(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        Cache::forever('catalog:version', 11);

        $this->postJson('/api/v1/admin/cache/refresh-catalog')
            ->assertOk()
            ->assertJsonPath('data.refreshed', true)
            ->assertJsonPath('data.catalog_version', 12);

        $this->assertSame(12, (int) Cache::get('catalog:version'));
    }

    /**
     * Ensure customer cannot refresh catalog cache.
     */
    public function test_customer_cannot_refresh_catalog_cache(): void
    {
        $this->seed(RoleSeeder::class);

        $customer = User::factory()->create(['email_verified_at' => now()]);
        $customer->assignRole('customer');
        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/admin/cache/refresh-catalog')->assertForbidden();
    }
}
