<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(['CAD', 'AUD', 'SGD', 'AED', 'JPY']),
            'name' => fake()->currencyCode(),
            'symbol' => '$',
            'decimal_places' => 2,
            'rate_to_default' => fake()->randomFloat(6, 0.1, 150),
            'rate_source' => 'factory',
            'rate_updated_at' => now(),
            'symbol_position' => 'prefix',
            'thousands_separator' => ',',
            'decimal_separator' => '.',
            'is_enabled' => true,
            'is_default' => false,
            'sort_order' => 10,
        ];
    }
}
