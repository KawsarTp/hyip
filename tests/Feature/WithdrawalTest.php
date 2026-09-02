<?php

namespace Tests\Feature;

use App\KycStatus;
use App\LedgerAccountType;
use App\Models\KycProfile;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use App\Services\LedgerService;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\WalletType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_verified_user_request_reserves_available_funds(): void
    {
        $user = User::factory()->create();
        KycProfile::factory()->for($user)->create(['status' => KycStatus::Verified]);
        $method = WithdrawalMethod::factory()->create(['minimum_amount_minor' => 1000]);
        $this->creditAvailableWallet($user, 25000);

        $response = $this->actingAs($user)->post(route('withdrawals.store'), [
            'amount' => '100.00',
            'currency' => 'USD',
            'withdrawal_method_id' => $method->id,
            'destination_name' => 'Jordan Davis',
            'destination_address' => 'US00 1234 5678',
            'destination_network' => 'SWIFT',
        ]);

        $withdrawal = Withdrawal::query()->firstOrFail();
        $response->assertRedirect(route('withdrawals.show', $withdrawal));
        $this->assertSame('pending', $withdrawal->status->value);
        $this->assertSame(15000, $user->wallets()->where('type', WalletType::Available)->value('balance_minor'));
        $this->assertSame(10000, $user->wallets()->where('type', WalletType::Pending)->value('balance_minor'));
    }

    public function test_rejection_releases_the_reserved_amount(): void
    {
        $user = User::factory()->create();
        $method = WithdrawalMethod::factory()->create();
        $this->creditAvailableWallet($user, 10000);
        $withdrawal = app(WithdrawalService::class)->create($user, '50.00', 'USD', $method, ['destination_address' => 'manual'], 'withdrawal-test-reject');
        $admin = User::factory()->create(['role' => 'finance']);

        $this->actingAs($admin)->patch(route('admin.withdrawals.review', $withdrawal), [
            'decision' => 'rejected',
            'review_notes' => 'Destination could not be verified.',
        ])->assertRedirect(route('admin.withdrawals.show', $withdrawal));

        $this->assertSame('rejected', $withdrawal->fresh()->status->value);
        $this->assertSame(10000, $user->wallets()->where('type', WalletType::Available)->value('balance_minor'));
        $this->assertSame(0, $user->wallets()->where('type', WalletType::Pending)->value('balance_minor'));
    }

    public function test_completion_settles_net_amount_and_fee_once(): void
    {
        $user = User::factory()->create();
        $method = WithdrawalMethod::factory()->create(['fee_fixed_minor' => 100, 'fee_percent_bps' => 500]);
        $this->creditAvailableWallet($user, 20000);
        $withdrawal = app(WithdrawalService::class)->create($user, '100.00', 'USD', $method, ['destination_address' => 'manual'], 'withdrawal-test-complete');
        $admin = User::factory()->create(['role' => 'finance']);
        app(WithdrawalService::class)->approve($withdrawal, $admin->id, 'Verified destination.');
        app(WithdrawalService::class)->complete($withdrawal, 'manual-transfer-123', $admin->id);

        $this->assertSame('completed', $withdrawal->fresh()->status->value);
        $this->assertSame(9900, $withdrawal->fresh()->net_amount_minor);
        $this->assertSame(0, $user->wallets()->where('type', WalletType::Pending)->value('balance_minor'));
        $this->assertSame(3, LedgerEntry::query()->count());
    }

    private function creditAvailableWallet(User $user, int $amountMinor): void
    {
        $wallet = app(WalletService::class)->wallet($user, WalletType::Available);
        $cashAccount = app(LedgerService::class)->systemAccount('cash:test:'.$user->id, 'Test cash', 'USD', LedgerAccountType::Asset);

        app(LedgerService::class)->post([
            'type' => 'deposit',
            'currency' => 'USD',
            'description' => 'Test funding',
            'idempotency_key' => 'test-funding-'.$user->id,
            'lines' => [
                ['account_id' => $cashAccount->id, 'debit_minor' => $amountMinor],
                ['account_id' => $wallet->ledger_account_id, 'credit_minor' => $amountMinor],
            ],
        ]);
    }
}
