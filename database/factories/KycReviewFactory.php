<?php

namespace Database\Factories;

use App\KycStatus;
use App\Models\KycProfile;
use App\Models\KycReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KycReview>
 */
class KycReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kyc_profile_id' => KycProfile::factory(),
            'reviewer_id' => User::factory(),
            'decision' => KycStatus::Verified,
            'notes' => 'Identity details reviewed.',
        ];
    }
}
