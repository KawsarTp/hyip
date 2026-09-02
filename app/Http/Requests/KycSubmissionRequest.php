<?php

namespace App\Http\Requests;

use App\KycStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KycSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
    }

    public function rules(): array
    {
        $profile = $this->user()?->kycProfile;
        $requiresDocument = $profile === null
            || ! $profile->documents()->exists()
            || $profile->status === KycStatus::ResubmissionRequired;

        return [
            'legal_name' => ['required', 'string', 'max:120'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'nationality' => ['required', 'string', 'size:2', 'alpha'],
            'country' => ['required', 'string', 'size:2', 'alpha'],
            'tax_residency' => ['nullable', 'string', 'size:2', 'alpha'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:30'],
            'document_type' => [Rule::requiredIf($requiresDocument), Rule::in(['passport', 'national_id', 'drivers_license'])],
            'identity_document' => [Rule::requiredIf($requiresDocument), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
