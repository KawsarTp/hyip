<?php

namespace App\Http\Requests;

use App\KycStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestmentRequest extends FormRequest
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
            'investment_plan_id' => ['required', 'integer', Rule::exists('investment_plans', 'id')],
            'amount' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'risk_acknowledged' => ['accepted'],
        ];
    }
}
