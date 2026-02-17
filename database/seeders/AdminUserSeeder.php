<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed default admin user.
     */
    public function run(): void
    {
        $email = (string) env('SHOP_ADMIN_EMAIL', 'admin@shop.local');
        $plainPassword = trim((string) env('SHOP_ADMIN_PASSWORD', ''));
        $admin = User::query()->where('email', $email)->first();

        $payload = [
            'first_name' => 'Store',
            'last_name' => 'Admin',
            'name' => 'Store Admin',
            'phone' => '+1000000000',
            'email_verified_at' => now(),
            'is_active' => true,
        ];

        if ($plainPassword !== '') {
            $payload['password'] = Hash::make($plainPassword);
        } elseif (! $admin instanceof User) {
            $payload['password'] = Hash::make(Str::random(32));
        }

        if ($admin instanceof User) {
            $admin->update($payload);
        } else {
            $admin = User::query()->create([
                ...$payload,
                'email' => $email,
            ]);
        }

        $admin->assignRole(RoleName::ADMIN);
    }
}
