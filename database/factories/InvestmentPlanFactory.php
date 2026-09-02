<?php

namespace Database\Factories;

use App\Models\InvestmentPlan;
use App\PlanStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvestmentPlan>
 */
class InvestmentPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'description' => 'A diversified investment plan with a documented projected return schedule.',
            'currency' => 'USD',
            'risk_rating' => 'Moderate',
            'minimum_amount_minor' => 10000,
            'maximum_amount_minor' => 1000000,
            'capacity_minor' => null,
            'current_invested_minor' => 0,
            'term_days' => 365,
            'lock_days' => 365,
            'projected_rate_bps' => 1200,
            'rate_type' => 'annual_simple',
            'calculation_frequency' => 'daily',
            'day_count_basis' => 365,
            'compounding' => false,
            'status' => PlanStatus::Published,
            'version' => 1,
            'effective_at' => now(),
            'sort_order' => 0,
        ];
    }
}
