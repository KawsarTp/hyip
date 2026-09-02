<?php

namespace Database\Factories;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_gateway_id' => PaymentGatewayFactory::new(),
            'label' => 'Primary USD account',
            'bank_name' => fake()->company().' Bank',
            'account_name' => fake()->name(),
            'account_number' => fake()->numerify('############'),
            'iban' => 'US'.fake()->numerify('####################'),
            'swift_code' => fake()->lexify('????????'),
            'branch' => fake()->city(),
            'instructions' => 'Include your deposit reference in the transfer note.',
            'currency' => 'USD',
            'is_enabled' => true,
            'sort_order' => 0,
        ];
    }
}
