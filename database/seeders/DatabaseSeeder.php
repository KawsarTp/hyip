<?php

namespace Database\Seeders;

use App\AccountStatus;
use App\Models\PaymentGateway;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'role' => UserRole::User,
                'status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Arcadia Admin',
                'password' => 'password',
                'role' => UserRole::Admin,
                'status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $gateways = [
            ['name' => 'Stripe', 'driver' => 'stripe', 'method' => 'card'],
            ['name' => 'PayPal', 'driver' => 'paypal', 'method' => 'paypal'],
            ['name' => 'Crypto payments', 'driver' => 'crypto', 'method' => 'crypto'],
            ['name' => 'Bank transfer', 'driver' => 'bank_transfer', 'method' => 'bank_transfer'],
        ];

        foreach ($gateways as $order => $gateway) {
            PaymentGateway::firstOrCreate(
                ['driver' => $gateway['driver'], 'name' => $gateway['name']],
                [
                    'method' => $gateway['method'],
                    'credentials' => [],
                    'settings' => [],
                    'is_enabled' => false,
                    'is_test_mode' => true,
                    'sort_order' => $order,
                ],
            );
        }
    }
}
