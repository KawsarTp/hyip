<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KycReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdminArea() ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['verified', 'rejected', 'resubmission_required'])],
            'review_notes' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn (): bool => $this->string('decision')->toString() !== 'verified')],
        ];
    }
}
