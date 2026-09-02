<?php

namespace Database\Factories;

use App\Models\WithdrawalMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithdrawalMethod>
 */
class WithdrawalMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' withdrawals',
            'method' => fake()->randomElement(['bank_transfer', 'crypto', 'paypal', 'manual']),
            'currency' => 'USD',
            'instructions' => 'Finance reviews this request manually. Use the destination details exactly as submitted.',
            'destination_label' => 'Destination details',
            'minimum_amount_minor' => 1000,
            'maximum_amount_minor' => 1000000,
            'fee_fixed_minor' => 0,
            'fee_percent_bps' => 0,
            'is_enabled' => true,
            'sort_order' => 0,
        ];
    }
}
