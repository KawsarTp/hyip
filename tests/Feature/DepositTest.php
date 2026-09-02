<?php

namespace Tests\Feature;

use App\DepositStatus;
use App\KycStatus;
use App\Models\BankAccount;
use App\Models\Deposit;
use App\Models\KycProfile;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_deposits(): void
    {
        $this->get(route('deposits.index'))
            ->assertRedirect(route('login'));
    }

    public function test_verified_users_can_create_pending_deposits_without_crediting_wallets(): void
    {
        $user = User::factory()->create();
        KycProfile::factory()->for($user)->create(['status' => KycStatus::Verified]);

        $gateway = PaymentGateway::factory()->create(['driver' => 'bank_transfer', 'method' => 'bank_transfer']);
        BankAccount::factory()->for($gateway, 'paymentGateway')->create();

        $response = $this->actingAs($user)->post(route('deposits.store'), [
            'amount' => '125.50',
            'currency' => 'USD',
            'payment_gateway_id' => $gateway->id,
            'idempotency_key' => 'deposit-test-001',
        ]);

        $deposit = Deposit::query()->where('idempotency_key', 'deposit-test-001')->firstOrFail();
        $response->assertRedirect(route('deposits.show', $deposit));
        $this->assertSame(DepositStatus::Pending, $deposit->status);
        $this->assertSame(12550, $deposit->amount_minor);
        $this->assertDatabaseCount('ledger_entries', 0);
    }

    public function test_finance_can_settle_a_deposit_and_credit_the_available_wallet(): void
    {
        $admin = User::factory()->create(['role' => 'finance']);
        $user = User::factory()->create();
        KycProfile::factory()->for($user)->create(['status' => KycStatus::Verified]);
        $gateway = PaymentGateway::factory()->create(['driver' => 'bank_transfer', 'method' => 'bank_transfer']);
        BankAccount::factory()->for($gateway, 'paymentGateway')->create();
        $deposit = app(DepositService::class)->create($user, '80.00', 'USD', $gateway, 'deposit-test-002');

        $this->actingAs($admin)->patch(route('admin.deposits.review', $deposit), [
            'decision' => 'completed',
            'external_reference' => 'bank-transaction-7788',
        ])->assertRedirect(route('admin.deposits.show', $deposit));

        $this->assertSame(DepositStatus::Completed, $deposit->fresh()->status);
        $this->assertSame(8000, $user->wallets()->where('type', 'available')->value('balance_minor'));
        $this->assertDatabaseCount('ledger_entries', 1);
    }

    public function test_a_signed_provider_webhook_is_processed_only_once(): void
    {
        config(['services.payments.webhook_secret' => 'local-webhook-secret']);
        $user = User::factory()->create();
        $gateway = PaymentGateway::factory()->create(['driver' => 'bank_transfer', 'method' => 'bank_transfer']);
        BankAccount::factory()->for($gateway, 'paymentGateway')->create();
        $deposit = app(DepositService::class)->create($user, '42.00', 'USD', $gateway, 'deposit-test-003');
        $payload = [
            'event_id' => 'provider-event-001',
            'event_type' => 'payment.succeeded',
            'payment_intent_reference' => $deposit->paymentIntent->reference,
            'amount_minor' => 4200,
            'currency' => 'USD',
            'external_reference' => 'provider-payment-001',
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, 'local-webhook-secret');

        $this->postJson(route('webhooks.payments', 'generic'), $payload, ['X-Payment-Signature' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'processed']);
        $this->postJson(route('webhooks.payments', 'generic'), $payload, ['X-Payment-Signature' => $signature])
            ->assertOk();

        $this->assertDatabaseCount('deposit_events', 1);
        $this->assertSame(4200, $user->wallets()->where('type', 'available')->value('balance_minor'));
    }
}
