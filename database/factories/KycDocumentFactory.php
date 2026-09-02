<?php

namespace Database\Factories;

use App\Models\KycDocument;
use App\Models\KycProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KycDocument>
 */
class KycDocumentFactory extends Factory
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
            'user_id' => User::factory(),
            'document_type' => 'passport',
            'path' => 'kyc-documents/example.pdf',
            'original_name' => 'passport.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'status' => 'pending',
        ];
    }
}
