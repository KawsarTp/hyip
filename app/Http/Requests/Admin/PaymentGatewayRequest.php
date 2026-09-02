<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentGatewayRequest extends FormRequest
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
            'driver' => ['required', Rule::in(['stripe', 'paypal', 'crypto', 'bank_transfer'])],
            'method' => ['required', Rule::in(['card', 'crypto', 'bank_transfer', 'manual'])],
            'publishable_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'webhook_id' => ['nullable', 'string', 'max:255'],
            'network' => ['nullable', 'string', 'max:80'],
            'wallet_address' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'is_enabled' => ['nullable', 'boolean'],
            'is_test_mode' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
