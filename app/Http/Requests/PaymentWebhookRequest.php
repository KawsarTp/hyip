<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $secret = config('services.payments.webhook_secret');
        $signature = $this->header('X-Payment-Signature');

        return is_string($secret)
            && $secret !== ''
            && is_string($signature)
            && hash_equals(hash_hmac('sha256', $this->getContent(), $secret), $signature);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'string', 'max:191'],
            'event_type' => ['required', Rule::in(['payment.succeeded', 'payment.failed'])],
            'payment_intent_reference' => ['required', 'uuid'],
            'provider_intent_id' => ['nullable', 'string', 'max:191'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3', 'uppercase'],
            'external_reference' => ['nullable', 'string', 'max:191'],
            'failure_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
