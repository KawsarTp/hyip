<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\KycStatus;

class WithdrawalRequest extends FormRequest
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
            'currency' => ['required', 'string', 'size:3', 'uppercase', Rule::in(['USD'])],
            'withdrawal_method_id' => ['required', 'integer', Rule::exists('withdrawal_methods', 'id')],
            'destination_name' => ['required', 'string', 'max:120'],
            'destination_address' => ['required', 'string', 'max:255'],
            'destination_network' => ['nullable', 'string', 'max:80'],
            'destination_note' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'string', 'max:100', 'alpha_dash'],
        ];
    }
}
