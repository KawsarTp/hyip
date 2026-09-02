<?php

namespace App\Contracts;

use App\Models\PaymentGateway;
use App\Models\PaymentIntent;

interface PaymentGatewayAdapter
{
    /**
     * @return array{provider_intent_id: string|null, status: string, checkout_url: string|null, metadata: array<string, mixed>}
     */
    public function createPayment(PaymentIntent $paymentIntent, PaymentGateway $gateway): array;

    /**
     * @return array{event_id: string, event_type: string, payment_intent_reference: string, provider_intent_id: string|null, amount_minor: int, currency: string, external_reference: string|null, failure_reason: string|null}
     */
    public function normalizeWebhook(PaymentGateway $gateway, string $payload, array $headers): array;
}
