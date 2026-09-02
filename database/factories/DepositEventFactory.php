<?php

namespace Database\Factories;

use App\Models\DepositEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DepositEvent>
 */
class DepositEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_intent_id' => PaymentIntentFactory::new(),
            'deposit_id' => DepositFactory::new(),
            'provider' => 'generic',
            'provider_event_id' => (string) Str::uuid(),
            'event_type' => 'payment.succeeded',
            'payload' => [],
            'status' => 'received',
            'processed_at' => null,
            'error_message' => null,
        ];
    }
}
