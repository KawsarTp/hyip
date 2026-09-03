<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WithdrawalMethodRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'method' => ['required', Rule::in(['bank_transfer', 'crypto', 'paypal', 'card', 'manual'])],
            'currency' => ['required', 'string', 'size:3', 'uppercase', Rule::exists('currencies', 'code')->where('is_enabled', true)],
            'instructions' => ['required', 'string', 'max:3000'],
            'destination_label' => ['required', 'string', 'max:100'],
            'minimum_amount' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'maximum_amount' => ['nullable', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'fee_fixed' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'fee_percent' => ['required', 'regex:/^\d{1,3}(\.\d{1,2})?$/', 'numeric', 'min:0', 'max:100'],
            'is_enabled' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
