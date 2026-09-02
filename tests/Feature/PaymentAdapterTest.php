<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\PaymentGateway;
use App\Models\PaymentIntent;
use App\Services\Payments\PayPalAdapter;
use App\Services\Payments\StripeAdapter;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentAdapterTest extends TestCase
{
    public function test_stripe_adapter_creates_a_checkout_session(): void
    {
        Http::fake([
            'https://api.stripe.com/*' => Http::response(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.test/session'], 200),
        ]);
        $gateway = PaymentGateway::make(['credentials' => ['secret_key' => 'sk_test_key']]);
        $intent = PaymentIntent::make(['reference' => 'intent-reference', 'currency' => 'USD', 'amount_minor' => 1250]);
        $deposit = Deposit::make();
        $deposit->id = 42;
        $intent->setRelation('deposit', $deposit);

        $result = app(StripeAdapter::class)->createPayment($intent, $gateway);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.stripe.com/v1/checkout/sessions' && $request->method() === 'POST');
        $this->assertSame('cs_test_123', $result['provider_intent_id']);
        $this->assertSame('https://checkout.stripe.test/session', $result['checkout_url']);
    }

    public function test_stripe_webhook_signature_is_normalized(): void
    {
        $gateway = PaymentGateway::make(['credentials' => ['webhook_secret' => 'whsec_test']]);
        $payload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_123',
                'client_reference_id' => 'intent-reference',
                'amount_total' => 1250,
                'currency' => 'usd',
                'payment_status' => 'paid',
                'metadata' => ['payment_intent_reference' => 'intent-reference'],
            ]],
        ], JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        $result = app(StripeAdapter::class)->normalizeWebhook($gateway, $payload, ['stripe-signature' => $signature]);

        $this->assertSame('evt_test_123', $result['event_id']);
        $this->assertSame('payment.succeeded', $result['event_type']);
        $this->assertSame(1250, $result['amount_minor']);
    }

    public function test_paypal_adapter_requests_an_order_and_returns_approval_url(): void
    {
        Http::fakeSequence()
            ->push(['access_token' => 'access-token'], 200)
            ->push(['id' => 'order_123', 'links' => [['rel' => 'approve', 'href' => 'https://paypal.test/approve']]], 201);
        $gateway = PaymentGateway::make(['is_test_mode' => true, 'credentials' => ['client_id' => 'client', 'client_secret' => 'secret']]);
        $intent = PaymentIntent::make(['reference' => 'intent-reference', 'currency' => 'USD', 'amount_minor' => 4200]);
        $deposit = Deposit::make();
        $deposit->id = 42;
        $intent->setRelation('deposit', $deposit);

        $result = app(PayPalAdapter::class)->createPayment($intent, $gateway);

        $this->assertSame('order_123', $result['provider_intent_id']);
        $this->assertSame('https://paypal.test/approve', $result['checkout_url']);
    }
}
