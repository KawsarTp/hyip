<?php

namespace Database\Factories;

use App\KycStatus;
use App\Models\KycProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KycProfile>
 */
class KycProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => KycStatus::InReview,
            'legal_name' => fake()->name(),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-21 years')->format('Y-m-d'),
            'nationality' => 'US',
            'country' => 'US',
            'tax_residency' => 'US',
            'address_line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'region' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'submitted_at' => now(),
        ];
    }
}
