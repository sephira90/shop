<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure manager can create and update category.
     */
    public function test_manager_can_create_and_update_category(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $create = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronics category',
            'is_active' => true,
            'sort_order' => 10,
        ])->assertCreated();

        $categoryId = (int) $create->json('data.id');

        $this->putJson('/api/v1/admin/categories/'.$categoryId, [
            'name' => 'Electronics and gadgets',
            'slug' => 'electronics-gadgets',
            'description' => 'Updated description',
            'is_active' => true,
            'sort_order' => 20,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Electronics and gadgets')
            ->assertJsonPath('data.sort_order', 20);

        $this->assertDatabaseHas('categories', [
            'id' => $categoryId,
            'name' => 'Electronics and gadgets',
            'slug' => 'electronics-gadgets',
        ]);
    }

    /**
     * Ensure manager cannot delete category.
     */
    public function test_manager_cannot_delete_category(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $category = Category::query()->create([
            'name' => 'Home',
            'slug' => 'home',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $this->deleteJson('/api/v1/admin/categories/'.$category->id)->assertForbidden();
    }

    /**
     * Ensure admin can delete category.
     */
    public function test_admin_can_delete_category(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $category = Category::query()->create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
            'sort_order' => 15,
        ]);

        $this->deleteJson('/api/v1/admin/categories/'.$category->id)->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }
}
