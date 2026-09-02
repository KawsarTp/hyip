<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReferralSettingsRequest extends FormRequest
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
            'investment_commission' => ['required', 'numeric', 'min:0', 'max:100'],
            'hold_days' => ['required', 'integer', 'min:0', 'max:365'],
            'minimum_qualifying_investment' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
