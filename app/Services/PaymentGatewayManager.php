<?php

namespace App\Services;

use App\Contracts\PaymentGatewayAdapter;
use App\Models\PaymentGateway;
use App\Models\PaymentIntent;
use App\PaymentIntentStatus;
use App\Services\Payments\ManualAdapter;
use App\Services\Payments\PayPalAdapter;
use App\Services\Payments\StripeAdapter;

class PaymentGatewayManager
{
    public function __construct(
        private readonly StripeAdapter $stripeAdapter,
        private readonly PayPalAdapter $payPalAdapter,
        private readonly ManualAdapter $manualAdapter,
    ) {}

    public function createPayment(PaymentIntent $paymentIntent, PaymentGateway $gateway): PaymentIntent
    {
        $result = $this->adapter($gateway)->createPayment($paymentIntent, $gateway);
        $metadata = array_merge($paymentIntent->metadata ?? [], $result['metadata'], array_filter([
            'checkout_url' => $result['checkout_url'],
        ], fn (mixed $value): bool => $value !== null));

        $paymentIntent->update([
            'provider_intent_id' => $result['provider_intent_id'],
            'status' => PaymentIntentStatus::from($result['status']),
            'metadata' => $metadata,
        ]);

        return $paymentIntent->fresh(['paymentGateway', 'deposit']);
    }

    /**
     * @return array{event_id: string, event_type: string, payment_intent_reference: string, provider_intent_id: string|null, amount_minor: int, currency: string, external_reference: string|null, failure_reason: string|null}
     */
    public function normalizeWebhook(PaymentGateway $gateway, string $payload, array $headers): array
    {
        return $this->adapter($gateway)->normalizeWebhook($gateway, $payload, $headers);
    }

    private function adapter(PaymentGateway $gateway): PaymentGatewayAdapter
    {
        return match ($gateway->driver) {
            'stripe' => $this->stripeAdapter,
            'paypal' => $this->payPalAdapter,
            'crypto', 'bank_transfer' => $this->manualAdapter,
            default => throw new \InvalidArgumentException('Unsupported payment gateway driver.'),
        };
    }
}
