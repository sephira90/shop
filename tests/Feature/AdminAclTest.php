<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAclTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure customer cannot access admin endpoint.
     */
    public function test_customer_forbidden_for_admin_area(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/products')->assertForbidden();
    }

    /**
     * Ensure manager can access admin endpoint.
     */
    public function test_manager_can_access_admin_area(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('manager');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/products')->assertOk();
    }
}
