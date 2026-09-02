<?php

namespace Database\Factories;

use App\LedgerEntryStatus;
use App\LedgerEntryType;
use App\Models\LedgerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => (string) Str::uuid(),
            'type' => LedgerEntryType::Adjustment,
            'status' => LedgerEntryStatus::Posted,
            'currency' => 'USD',
            'description' => fake()->sentence(4),
            'idempotency_key' => null,
            'actor_id' => null,
            'payload_hash' => null,
            'metadata' => null,
            'posted_at' => now(),
        ];
    }
}
