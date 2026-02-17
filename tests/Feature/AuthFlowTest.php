<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure user can register and fetch profile.
     */
    public function test_register_and_me_flow(): void
    {
        $this->seed(RoleSeeder::class);

        $register = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'verysecurepassword',
            'password_confirmation' => 'verysecurepassword',
        ]);

        $register->assertCreated();
        $token = $register->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'john@example.com');
    }
}
