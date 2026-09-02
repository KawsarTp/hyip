<?php

namespace Database\Factories;

use App\InvestmentStatus;
use App\Models\Investment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Investment>
 */
class InvestmentFactory extends Factory
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
            'investment_plan_id' => InvestmentPlanFactory::new(),
            'reference' => (string) Str::uuid(),
            'plan_version' => 1,
            'currency' => 'USD',
            'principal_minor' => 10000,
            'projected_return_minor' => 1200,
            'accrued_return_minor' => 0,
            'released_return_minor' => 0,
            'status' => InvestmentStatus::Active,
            'start_at' => now(),
            'maturity_at' => now()->addYear(),
            'last_calculated_at' => null,
            'matured_at' => null,
            'completed_at' => null,
            'risk_acknowledged_at' => now(),
            'terms_snapshot' => ['name' => 'Test plan', 'rate_type' => 'annual_simple', 'projected_rate_bps' => 1200, 'term_days' => 365, 'day_count_basis' => 365, 'calculation_frequency' => 'daily'],
            'metadata' => null,
        ];
    }
}
