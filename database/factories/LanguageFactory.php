<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        $code = fake()->unique()->randomElement(['fr', 'de', 'es', 'ar', 'pt']);

        return [
            'code' => $code,
            'name' => ucfirst($code),
            'native_name' => ucfirst($code),
            'direction' => $code === 'ar' ? 'rtl' : 'ltr',
            'is_enabled' => true,
            'is_default' => false,
            'sort_order' => 10,
        ];
    }
}
