<?php

namespace Database\Factories;

use App\LedgerAccountType;
use App\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerAccount>
 */
class LedgerAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'currency' => 'USD',
            'type' => LedgerAccountType::Asset,
        ];
    }
}
