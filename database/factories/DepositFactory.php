<?php

namespace Database\Factories;

use App\DepositStatus;
use App\Models\Deposit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'payment_intent_id' => PaymentIntentFactory::new(),
            'reference' => (string) Str::uuid(),
            'idempotency_key' => null,
            'method' => 'bank_transfer',
            'currency' => 'USD',
            'amount_minor' => 10000,
            'status' => DepositStatus::Pending,
            'external_reference' => null,
            'settled_at' => null,
            'rejection_reason' => null,
            'metadata' => null,
        ];
    }
}
