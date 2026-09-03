<?php

namespace App\Http\Requests;

use App\KycStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepositRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isActive() === true
            && $this->user()?->hasVerifiedEmail() === true
            && $this->user()?->kycProfile?->status === KycStatus::Verified;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'currency' => ['required', 'string', 'size:3', 'uppercase', Rule::exists('currencies', 'code')->where('is_enabled', true)],
            'payment_gateway_id' => ['required', 'integer', Rule::exists('payment_gateways', 'id')],
            'idempotency_key' => ['nullable', 'string', 'max:100', 'alpha_dash'],
        ];
    }
}
