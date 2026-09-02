<?php

namespace Tests\Feature;

use App\KycStatus;
use App\Models\KycDocument;
use App\Models\KycProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_kyc(): void
    {
        $this->get(route('kyc.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_an_active_user_can_submit_kyc_details_and_a_private_document(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('kyc.update'), [
            'legal_name' => 'Jordan Davis',
            'date_of_birth' => '1990-05-12',
            'nationality' => 'US',
            'country' => 'US',
            'tax_residency' => 'US',
            'address_line1' => '100 Main Street',
            'city' => 'Austin',
            'region' => 'TX',
            'postal_code' => '78701',
            'document_type' => 'passport',
            'identity_document' => UploadedFile::fake()->create('passport.pdf', 1024, 'application/pdf'),
        ]);

        $response->assertRedirect(route('kyc.edit'));
        $profile = KycProfile::where('user_id', $user->id)->firstOrFail();
        $document = KycDocument::where('kyc_profile_id', $profile->id)->firstOrFail();

        $this->assertSame(KycStatus::InReview, $profile->status);
        $this->assertSame('pending', $document->status);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_a_compliance_admin_can_approve_a_kyc_profile(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Compliance]);
        $user = User::factory()->create();
        $profile = KycProfile::factory()->for($user)->create(['status' => KycStatus::InReview]);
        $document = KycDocument::create([
            'kyc_profile_id' => $profile->id,
            'user_id' => $user->id,
            'document_type' => 'passport',
            'path' => 'kyc-documents/passport.pdf',
            'original_name' => 'passport.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->patch(route('admin.kyc.update', $profile), [
            'decision' => 'verified',
            'review_notes' => '',
        ])->assertRedirect(route('admin.kyc.show', $profile));

        $this->assertSame(KycStatus::Verified, $profile->fresh()->status);
        $this->assertSame('approved', $document->fresh()->status);
        $this->assertSame($admin->id, $profile->fresh()->reviewed_by);
        $this->assertDatabaseHas('kyc_reviews', [
            'kyc_profile_id' => $profile->id,
            'reviewer_id' => $admin->id,
            'decision' => KycStatus::Verified->value,
        ]);
    }

    public function test_regular_users_cannot_review_kyc_profiles(): void
    {
        $user = User::factory()->create();
        $profile = KycProfile::factory()->for($user)->create();

        $this->actingAs($user)->get(route('admin.kyc.show', $profile))
            ->assertForbidden();
    }
}
