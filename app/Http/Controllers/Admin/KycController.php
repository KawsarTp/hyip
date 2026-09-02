<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\KycReviewRequest;
use App\KycStatus;
use App\Models\KycDocument;
use App\Models\KycProfile;
use App\Models\KycReview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    public function index(): View
    {
        $profiles = KycProfile::query()
            ->with('user')
            ->whereIn('status', [KycStatus::InReview, KycStatus::ResubmissionRequired])
            ->latest('submitted_at')
            ->paginate(15);

        return view('admin.kyc.index', compact('profiles'));
    }

    public function show(KycProfile $kycProfile): View
    {
        $kycProfile->load(['user', 'documents', 'reviewer', 'reviews.reviewer']);

        return view('admin.kyc.show', compact('kycProfile'));
    }

    public function update(KycReviewRequest $request, KycProfile $kycProfile): RedirectResponse
    {
        $decision = KycStatus::from($request->string('decision')->toString());
        $notes = $request->string('review_notes')->trim()->toString() ?: null;

        DB::transaction(function () use ($request, $kycProfile, $decision, $notes): void {
            KycReview::create([
                'kyc_profile_id' => $kycProfile->id,
                'reviewer_id' => $request->user()->id,
                'decision' => $decision,
                'notes' => $notes,
            ]);

            $kycProfile->update([
                'status' => $decision,
                'review_notes' => $notes,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $kycProfile->documents()->where('status', 'pending')->update([
                'status' => $decision === KycStatus::Verified ? 'approved' : 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $decision === KycStatus::Verified ? null : $notes,
            ]);
        });

        return redirect()->route('admin.kyc.show', $kycProfile)->with('status', 'KYC review updated.');
    }

    public function downloadDocument(KycDocument $kycDocument): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($kycDocument->path), 404);

        return Storage::disk('local')->download($kycDocument->path, $kycDocument->original_name);
    }
}
