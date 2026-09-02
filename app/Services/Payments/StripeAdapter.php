<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayAdapter;
use App\Exceptions\PaymentProviderException;
use App\Models\PaymentGateway;
use App\Models\PaymentIntent;
use App\PaymentIntentStatus;
use Illuminate\Support\Facades\Http;
use JsonException;

class StripeAdapter implements PaymentGatewayAdapter
{
    /** @return array{provider_intent_id: string|null, status: string, checkout_url: string|null, metadata: array<string, mixed>} */
    public function createPayment(PaymentIntent $paymentIntent, PaymentGateway $gateway): array
    {
        $secretKey = $gateway->credentials['secret_key'] ?? null;

        if (! is_string($secretKey) || $secretKey === '') {
            throw new PaymentProviderException('Stripe secret key is not configured.');
        }

        $deposit = $paymentIntent->deposit;
        $response = Http::asForm()->withToken($secretKey)->timeout(20)->post('https://api.stripe.com/v1/checkout/sessions', [
            'mode' => 'payment',
            'line_items[0][price_data][currency]' => strtolower($paymentIntent->currency),
            'line_items[0][price_data][product_data][name]' => 'Arcadia wallet deposit',
            'line_items[0][price_data][unit_amount]' => $paymentIntent->amount_minor,
            'line_items[0][quantity]' => 1,
            'client_reference_id' => $paymentIntent->reference,
            'metadata[payment_intent_reference]' => $paymentIntent->reference,
            'success_url' => route('deposits.show', $deposit).'?payment=success',
            'cancel_url' => route('deposits.show', $deposit).'?payment=cancelled',
        ]);

        if ($response->failed()) {
            throw new PaymentProviderException('Stripe checkout creation failed: '.$this->providerMessage($response->json()));
        }

        $data = $response->json();

        return [
            'provider_intent_id' => $data['id'] ?? null,
            'status' => PaymentIntentStatus::RequiresAction->value,
            'checkout_url' => $data['url'] ?? null,
            'metadata' => ['stripe_session_id' => $data['id'] ?? null],
        ];
    }

    /** @return array{event_id: string, event_type: string, payment_intent_reference: string, provider_intent_id: string|null, amount_minor: int, currency: string, external_reference: string|null, failure_reason: string|null} */
    public function normalizeWebhook(PaymentGateway $gateway, string $payload, array $headers): array
    {
        $secret = $gateway->credentials['webhook_secret'] ?? null;
        $signature = $this->header($headers, 'stripe-signature');

        if (! is_string($secret) || $secret === '' || $signature === null) {
            throw new PaymentProviderException('Stripe webhook secret or signature is missing.');
        }

        $parts = collect(explode(',', $signature))->mapWithKeys(function (string $part): array {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

            return [$key => $value];
        });
        $timestamp = (int) $parts->get('t', 0);
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        $provided = $parts->get('v1');

        if (abs(now()->timestamp - $timestamp) > 300 || $provided === null || ! hash_equals($expected, $provided)) {
            throw new PaymentProviderException('Stripe webhook signature verification failed.');
        }

        try {
            $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PaymentProviderException('Stripe webhook payload is invalid.', previous: $exception);
        }

        $object = $event['data']['object'] ?? [];
        $metadata = $object['metadata'] ?? [];
        $eventType = $event['type'] ?? '';
        $succeeded = $eventType === 'payment_intent.succeeded'
            || ($eventType === 'checkout.session.completed' && ($object['payment_status'] ?? null) === 'paid');
        $failed = $eventType === 'payment_intent.payment_failed';

        if (! $succeeded && ! $failed) {
            throw new PaymentProviderException('Unsupported Stripe webhook event.');
        }

        $amountMinor = (int) ($object['amount_total'] ?? $object['amount_received'] ?? $object['amount'] ?? 0);

        $paymentIntentReference = (string) ($metadata['payment_intent_reference'] ?? $object['client_reference_id'] ?? '');

        if (($event['id'] ?? '') === '' || $paymentIntentReference === '') {
            throw new PaymentProviderException('Stripe webhook is missing its event or payment reference.');
        }

        return [
            'event_id' => (string) ($event['id'] ?? ''),
            'event_type' => $succeeded ? 'payment.succeeded' : 'payment.failed',
            'payment_intent_reference' => $paymentIntentReference,
            'provider_intent_id' => $object['payment_intent'] ?? $object['id'] ?? null,
            'amount_minor' => $amountMinor,
            'currency' => strtoupper((string) ($object['currency'] ?? '')),
            'external_reference' => $object['id'] ?? null,
            'failure_reason' => $object['last_payment_error']['message'] ?? null,
        ];
    }

    private function header(array $headers, string $name): ?string
    {
        $value = $headers[$name] ?? $headers[strtolower($name)] ?? null;

        return is_array($value) ? ($value[0] ?? null) : $value;
    }

    private function providerMessage(array $payload): string
    {
        return (string) ($payload['error']['message'] ?? 'Unknown provider error.');
    }
}
