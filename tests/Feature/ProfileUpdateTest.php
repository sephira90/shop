<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure authenticated user can update own profile fields.
     */
    public function test_authenticated_user_can_update_profile(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'name' => 'Old Name',
            'phone' => null,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/auth/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '+15551234567',
        ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.last_name', 'Doe')
            ->assertJsonPath('data.name', 'Jane Doe')
            ->assertJsonPath('data.phone', '+15551234567');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'phone' => '+15551234567',
        ]);
    }

    /**
     * Ensure profile update requires authentication.
     */
    public function test_guest_cannot_update_profile(): void
    {
        $this->patchJson('/api/v1/auth/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ])->assertUnauthorized();
    }
}
