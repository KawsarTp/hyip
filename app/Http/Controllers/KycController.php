<?php

namespace App\Http\Controllers;

use App\Http\Requests\KycSubmissionRequest;
use App\KycStatus;
use App\Models\KycProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    public function edit(): View
    {
        $profile = auth()->user()->kycProfile()->with('documents')->first();

        return view('kyc.edit', compact('profile'));
    }

    public function update(KycSubmissionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $document = $request->file('identity_document');
        $path = $document?->store('kyc-documents', 'local');

        try {
            DB::transaction(function () use ($request, $validated, $path, $document): void {
                $profile = KycProfile::firstOrNew(['user_id' => $request->user()->id]);
                $profile->fill([
                    'status' => KycStatus::InReview,
                    'legal_name' => $validated['legal_name'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'nationality' => strtoupper($validated['nationality']),
                    'country' => strtoupper($validated['country']),
                    'tax_residency' => isset($validated['tax_residency']) ? strtoupper($validated['tax_residency']) : null,
                    'address_line1' => $validated['address_line1'],
                    'address_line2' => $validated['address_line2'] ?? null,
                    'city' => $validated['city'],
                    'region' => $validated['region'] ?? null,
                    'postal_code' => $validated['postal_code'],
                    'review_notes' => null,
                    'reviewed_by' => null,
                    'submitted_at' => now(),
                    'reviewed_at' => null,
                ]);
                $profile->save();

                if ($document && $path) {
                    $profile->documents()->create([
                        'user_id' => $request->user()->id,
                        'document_type' => $validated['document_type'],
                        'path' => $path,
                        'original_name' => $document->getClientOriginalName(),
                        'mime_type' => $document->getMimeType(),
                        'size' => $document->getSize(),
                        'status' => 'pending',
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return redirect()->route('kyc.edit')->with('status', 'Your verification details are in review.');
    }
}
