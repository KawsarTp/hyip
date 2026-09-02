<?php

namespace Database\Factories;

use App\Models\Withdrawal;
use App\WithdrawalStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Withdrawal>
 */
class WithdrawalFactory extends Factory
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
            'withdrawal_method_id' => WithdrawalMethodFactory::new(),
            'reference' => (string) Str::uuid(),
            'idempotency_key' => null,
            'currency' => 'USD',
            'gross_amount_minor' => 10000,
            'fee_amount_minor' => 0,
            'net_amount_minor' => 10000,
            'status' => WithdrawalStatus::Pending,
            'destination_details' => ['destination_address' => 'manual-destination'],
            'external_reference' => null,
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reserved_at' => null,
            'reviewed_at' => null,
            'completed_at' => null,
            'metadata' => null,
        ];
    }
}
