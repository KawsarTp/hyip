<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayAdapter;
use App\Exceptions\PaymentProviderException;
use App\Models\PaymentGateway;
use App\Models\PaymentIntent;
use App\PaymentIntentStatus;
use Illuminate\Support\Facades\Http;
use JsonException;

class PayPalAdapter implements PaymentGatewayAdapter
{
    /** @return array{provider_intent_id: string|null, status: string, checkout_url: string|null, metadata: array<string, mixed>} */
    public function createPayment(PaymentIntent $paymentIntent, PaymentGateway $gateway): array
    {
        $token = $this->accessToken($gateway);
        $baseUrl = $this->baseUrl($gateway);
        $deposit = $paymentIntent->deposit;
        $response = Http::withToken($token)->acceptJson()->timeout(20)->post($baseUrl.'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $paymentIntent->reference,
                'custom_id' => $paymentIntent->reference,
                'amount' => [
                    'currency_code' => $paymentIntent->currency,
                    'value' => $this->minorToMajor($paymentIntent->amount_minor),
                ],
            ]],
            'application_context' => [
                'user_action' => 'PAY_NOW',
                'return_url' => route('deposits.show', $deposit).'?payment=success',
                'cancel_url' => route('deposits.show', $deposit).'?payment=cancelled',
            ],
        ]);

        if ($response->failed()) {
            throw new PaymentProviderException('PayPal order creation failed: '.($response->json('message') ?? 'Unknown provider error.'));
        }

        $data = $response->json();
        $approvalLink = collect($data['links'] ?? [])->firstWhere('rel', 'approve');
        $approvalUrl = $approvalLink['href'] ?? null;

        return [
            'provider_intent_id' => $data['id'] ?? null,
            'status' => PaymentIntentStatus::RequiresAction->value,
            'checkout_url' => $approvalUrl,
            'metadata' => ['paypal_order_id' => $data['id'] ?? null],
        ];
    }

    /** @return array{event_id: string, event_type: string, payment_intent_reference: string, provider_intent_id: string|null, amount_minor: int, currency: string, external_reference: string|null, failure_reason: string|null} */
    public function normalizeWebhook(PaymentGateway $gateway, string $payload, array $headers): array
    {
        $event = $this->decode($payload);
        $this->verifyWebhook($gateway, $event, $headers);
        $resource = $event['resource'] ?? [];
        $eventName = strtoupper((string) ($event['event_type'] ?? ''));
        $succeeded = in_array($eventName, ['PAYMENT.CAPTURE.COMPLETED', 'CHECKOUT.ORDER.COMPLETED'], true);
        $failed = in_array($eventName, ['PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED', 'PAYMENT.CAPTURE.REFUNDED'], true);

        if (! $succeeded && ! $failed) {
            throw new PaymentProviderException('Unsupported PayPal webhook event.');
        }

        $purchaseUnit = $resource['purchase_units'][0] ?? [];
        $amount = $resource['amount'] ?? $purchaseUnit['amount'] ?? [];

        $paymentIntentReference = (string) ($resource['custom_id'] ?? $purchaseUnit['custom_id'] ?? $purchaseUnit['reference_id'] ?? '');

        if (($event['id'] ?? '') === '' || $paymentIntentReference === '') {
            throw new PaymentProviderException('PayPal webhook is missing its event or payment reference.');
        }

        return [
            'event_id' => (string) ($event['id'] ?? ''),
            'event_type' => $succeeded ? 'payment.succeeded' : 'payment.failed',
            'payment_intent_reference' => $paymentIntentReference,
            'provider_intent_id' => $resource['supplementary_data']['related_ids']['order_id'] ?? $resource['id'] ?? null,
            'amount_minor' => $this->amountToMinor((string) ($amount['value'] ?? '0')),
            'currency' => strtoupper((string) ($amount['currency_code'] ?? '')),
            'external_reference' => $resource['id'] ?? null,
            'failure_reason' => $resource['status_details']['reason'] ?? null,
        ];
    }

    private function accessToken(PaymentGateway $gateway): string
    {
        $clientId = $gateway->credentials['client_id'] ?? null;
        $clientSecret = $gateway->credentials['client_secret'] ?? null;

        if (! is_string($clientId) || ! is_string($clientSecret) || $clientId === '' || $clientSecret === '') {
            throw new PaymentProviderException('PayPal client credentials are not configured.');
        }

        $response = Http::asForm()->withBasicAuth($clientId, $clientSecret)->timeout(20)->post($this->baseUrl($gateway).'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($response->failed() || ! is_string($response->json('access_token'))) {
            throw new PaymentProviderException('PayPal access token request failed.');
        }

        return $response->json('access_token');
    }

    private function verifyWebhook(PaymentGateway $gateway, array $event, array $headers): void
    {
        $credentials = $gateway->credentials ?? [];
        $webhookId = $credentials['webhook_id'] ?? null;
        $transmissionId = $this->header($headers, 'paypal-transmission-id');
        $transmissionTime = $this->header($headers, 'paypal-transmission-time');
        $transmissionSignature = $this->header($headers, 'paypal-transmission-sig');
        $certUrl = $this->header($headers, 'paypal-cert-url');
        $authAlgo = $this->header($headers, 'paypal-auth-algo');

        if (! is_string($webhookId) || $webhookId === '' || $transmissionId === null || $transmissionTime === null || $transmissionSignature === null || $certUrl === null || $authAlgo === null) {
            throw new PaymentProviderException('PayPal webhook verification data is incomplete.');
        }

        $response = Http::withToken($this->accessToken($gateway))->acceptJson()->timeout(20)->post($this->baseUrl($gateway).'/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $authAlgo,
            'cert_url' => $certUrl,
            'transmission_id' => $transmissionId,
            'transmission_sig' => $transmissionSignature,
            'transmission_time' => $transmissionTime,
            'webhook_id' => $webhookId,
            'webhook_event' => $event,
        ]);

        if ($response->failed() || $response->json('verification_status') !== 'SUCCESS') {
            throw new PaymentProviderException('PayPal webhook signature verification failed.');
        }
    }

    private function baseUrl(PaymentGateway $gateway): string
    {
        return $gateway->is_test_mode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    private function amountToMinor(string $amount): int
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new PaymentProviderException('PayPal returned an invalid amount.');
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    private function minorToMajor(int $amountMinor): string
    {
        return intdiv($amountMinor, 100).'.'.str_pad((string) ($amountMinor % 100), 2, '0', STR_PAD_LEFT);
    }

    private function decode(string $payload): array
    {
        try {
            return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PaymentProviderException('PayPal webhook payload is invalid.', previous: $exception);
        }
    }

    private function header(array $headers, string $name): ?string
    {
        $value = $headers[$name] ?? $headers[strtolower($name)] ?? null;

        return is_array($value) ? ($value[0] ?? null) : $value;
    }
}
