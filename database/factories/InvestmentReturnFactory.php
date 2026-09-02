<?php

namespace Database\Factories;

use App\InvestmentReturnStatus;
use App\Models\InvestmentReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvestmentReturn>
 */
class InvestmentReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'investment_id' => InvestmentFactory::new(),
            'period_key' => fake()->unique()->date('Y-m-d'),
            'period_start' => now()->subDay(),
            'period_end' => now(),
            'opening_principal_minor' => 10000,
            'eligible_days' => 1,
            'rate_bps' => 1200,
            'return_amount_minor' => 33,
            'status' => InvestmentReturnStatus::Accrued,
            'formula_version' => 'annual-simple-v1',
            'calculation_snapshot' => ['formula' => 'principal * annual_rate * eligible_days / day_count_basis'],
            'approved_by' => null,
            'ledger_entry_id' => null,
            'approved_at' => null,
            'released_at' => null,
        ];
    }
}
