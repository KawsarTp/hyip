<?php

namespace Database\Factories;

use App\Models\PaymentIntent;
use App\PaymentIntentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentIntent>
 */
class PaymentIntentFactory extends Factory
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
            'reference' => (string) Str::uuid(),
            'provider' => 'arcadia',
            'provider_intent_id' => null,
            'method' => 'bank_transfer',
            'currency' => 'USD',
            'amount_minor' => 10000,
            'status' => PaymentIntentStatus::Created,
            'idempotency_key' => null,
            'metadata' => null,
            'expires_at' => now()->addDay(),
            'succeeded_at' => null,
            'failure_reason' => null,
        ];
    }
}
