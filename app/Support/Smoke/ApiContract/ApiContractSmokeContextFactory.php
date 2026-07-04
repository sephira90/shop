<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ApiContractSmokeContextFactory
{
    public function build(): ApiContractSmokeContext
    {
        $this->seedRequiredData();

        return new ApiContractSmokeContext($this->resolveManagerToken());
    }

    private function seedRequiredData(): void
    {
        app(RoleSeeder::class)->run();
        app(CatalogSeeder::class)->run();
    }

    private function resolveManagerToken(): string
    {
        $manager = User::unguarded(/**
         * psalm/plugin-laravel on the v3.0.x line widens firstOrCreate() to User|Builder<User>; larastan narrows it to User.
         *
         * @psalm-suppress InvalidReturnType
         * @psalm-suppress InvalidReturnStatement
         */
            function (): User {
                $user = User::query()->firstOrCreate(
                    ['email' => 'api.contract.manager@shop.local'],
                    [
                        'first_name' => 'Api',
                        'last_name' => 'Contract',
                        'name' => 'Api Contract',
                        'password' => Hash::make(Str::random(32)),
                        'is_active' => true,
                    ],
                );

                if ($user->email_verified_at === null) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }

                return $user;
            });

        if (! $manager->is_active) {
            $manager->forceFill(['is_active' => true])->save();
        }

        $manager->assignRole(RoleName::MANAGER);

        return $manager->createToken('api-contract-smoke')->plainTextToken;
    }
}
