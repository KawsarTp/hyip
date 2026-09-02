<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Payments',
            'driver' => fake()->randomElement(['stripe', 'paypal', 'crypto', 'bank_transfer']),
            'method' => fake()->randomElement(['card', 'card', 'crypto', 'bank_transfer']),
            'credentials' => ['secret_key' => fake()->sha256()],
            'settings' => ['instructions' => 'Follow the payment instructions shown after creating a deposit.'],
            'is_enabled' => true,
            'is_test_mode' => true,
            'sort_order' => 0,
        ];
    }
}
