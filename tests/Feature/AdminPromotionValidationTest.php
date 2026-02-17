<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPromotionValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure percent promotions cannot exceed 100.
     */
    public function test_percent_promotion_value_above_hundred_is_rejected(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/admin/promotions', [
            'name' => 'Too high percent',
            'type' => 'percent',
            'value' => 150,
            'is_active' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'error.validation.value.0',
                'Percent value must be less or equal to 100.',
            );
    }
}
