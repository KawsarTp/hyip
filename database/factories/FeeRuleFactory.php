<?php

namespace Database\Factories;

use App\Models\FeeRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeRule>
 */
class FeeRuleFactory extends Factory
{
    protected $model = FeeRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Withdrawal standard',
            'event' => 'withdrawal',
            'currency' => 'USD',
            'country' => null,
            'fee_fixed_minor' => 100,
            'fee_percent_bps' => 0,
            'maximum_fee_minor' => null,
            'tax_fixed_minor' => 0,
            'tax_percent_bps' => 0,
            'tax_basis' => 'fee',
            'priority' => 10,
            'is_enabled' => true,
            'effective_from' => now()->subMinute(),
            'effective_until' => null,
        ];
    }
}
