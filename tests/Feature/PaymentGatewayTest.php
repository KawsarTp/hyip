<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_configure_a_gateway_and_credentials_are_encrypted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.payment-gateways.store'), [
            'name' => 'Stripe primary',
            'driver' => 'stripe',
            'method' => 'card',
            'secret_key' => 'sk_test_private_value',
            'webhook_secret' => 'whsec_private_value',
            'is_enabled' => '1',
            'is_test_mode' => '1',
            'sort_order' => 1,
        ])->assertRedirect(route('admin.payment-gateways.index'));

        $gateway = PaymentGateway::query()->firstOrFail();
        $this->assertSame('sk_test_private_value', $gateway->credentials['secret_key']);
        $this->assertNotSame('sk_test_private_value', DB::table('payment_gateways')->where('id', $gateway->id)->value('credentials'));
        $this->assertTrue($gateway->is_enabled);
    }

    public function test_admin_can_configure_an_encrypted_bank_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gateway = PaymentGateway::factory()->create(['driver' => 'bank_transfer', 'method' => 'bank_transfer']);

        $this->actingAs($admin)->post(route('admin.bank-accounts.store'), [
            'payment_gateway_id' => $gateway->id,
            'label' => 'Primary receiving account',
            'bank_name' => 'Arcadia Bank',
            'account_name' => 'Arcadia Holdings',
            'account_number' => '123456789',
            'iban' => 'US12345678901234567890',
            'swift_code' => 'ARCADIA1',
            'currency' => 'USD',
            'sort_order' => 1,
            'is_enabled' => '1',
        ])->assertRedirect(route('admin.bank-accounts.index'));

        $account = BankAccount::query()->firstOrFail();
        $this->assertSame('123456789', $account->account_number);
        $this->assertNotSame('123456789', DB::table('bank_accounts')->where('id', $account->id)->value('account_number'));
    }

    public function test_regular_users_cannot_manage_payment_gateways(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.payment-gateways.index'))
            ->assertForbidden();
    }
}
