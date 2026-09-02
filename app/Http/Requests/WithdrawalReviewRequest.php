<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WithdrawalReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdminArea() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'completed', 'rejected'])],
            'external_reference' => ['nullable', 'string', 'max:191', Rule::requiredIf(fn (): bool => $this->string('decision')->toString() === 'completed')],
            'review_notes' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn (): bool => in_array($this->string('decision')->toString(), ['approved', 'rejected'], true))],
        ];
    }
}
