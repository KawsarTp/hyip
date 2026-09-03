<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\TranslationLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranslationLine>
 */
class TranslationLineFactory extends Factory
{
    protected $model = TranslationLine::class;

    public function definition(): array
    {
        return [
            'language_id' => Language::factory(),
            'group_name' => 'messages',
            'key' => fake()->unique()->words(2, true),
            'value' => fake()->sentence(),
        ];
    }
}
