<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdminArea() === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'event' => ['required', Rule::in(['deposit', 'investment', 'referral', 'withdrawal'])],
            'currency' => ['nullable', 'string', 'size:3', 'uppercase'],
            'country' => ['nullable', 'string', 'size:2', 'uppercase'],
            'fee_fixed' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'fee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'maximum_fee' => ['nullable', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'tax_fixed' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_basis' => ['required', Rule::in(['fee', 'gross'])],
            'priority' => ['required', 'integer', 'min:0', 'max:10000'],
            'is_enabled' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
