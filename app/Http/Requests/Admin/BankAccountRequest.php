<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankAccountRequest extends FormRequest
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
            'payment_gateway_id' => ['required', 'integer', Rule::exists('payment_gateways', 'id')],
            'label' => ['required', 'string', 'max:100'],
            'bank_name' => ['required', 'string', 'max:150'],
            'account_name' => ['required', 'string', 'max:150'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'iban' => ['nullable', 'string', 'max:100'],
            'swift_code' => ['nullable', 'string', 'max:40'],
            'branch' => ['nullable', 'string', 'max:150'],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'currency' => ['required', 'string', 'size:3', 'uppercase', Rule::exists('currencies', 'code')->where('is_enabled', true)],
            'is_enabled' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
