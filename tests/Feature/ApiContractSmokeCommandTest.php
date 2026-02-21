<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContractSmokeCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure API contract smoke command passes for key endpoints.
     */
    public function test_api_contract_smoke_command_passes(): void
    {
        $this->artisan('app:api-contract-smoke')
            ->assertSuccessful()
            ->expectsOutputToContain('API contract smoke checks passed.');
    }

    /**
     * Ensure production execution does not persist smoke data by default.
     */
    public function test_api_contract_smoke_command_rolls_back_data_in_production(): void
    {
        config()->set('app.env', 'production');

        $this->artisan('app:api-contract-smoke')
            ->assertSuccessful()
            ->expectsOutputToContain('Production safeguard: smoke data rolled back.');

        $this->assertDatabaseMissing('users', ['email' => 'api.contract.manager@shop.local']);
        $this->assertDatabaseCount('products', 0);
    }
}
