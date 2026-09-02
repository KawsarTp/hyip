<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientWalletBalance;
use App\LedgerAccountType;
use App\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\WalletService;
use App\WalletType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_wallet(): void
    {
        $this->get(route('wallet.index'))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_receives_all_wallet_buckets_once(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);

        $wallets = $walletService->walletsFor($user);

        $this->assertCount(count(WalletType::cases()), $wallets);
        $this->assertCount(count(WalletType::cases()), $walletService->walletsFor($user));
        $this->assertSame(0, $wallets->firstWhere('type', WalletType::Available)->balance_minor);
    }

    public function test_a_balanced_entry_updates_a_wallet_in_minor_units(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->wallet($user, WalletType::Available);
        $systemAccount = app(LedgerService::class)->systemAccount('cash:usd', 'Cash clearing', 'USD', LedgerAccountType::Asset);

        $entry = app(LedgerService::class)->post([
            'type' => LedgerEntryType::Deposit,
            'currency' => 'USD',
            'description' => 'Approved deposit',
            'idempotency_key' => 'deposit-'.$user->id.'-001',
            'lines' => [
                ['account_id' => $systemAccount->id, 'debit_minor' => 12500],
                ['account_id' => $wallet->ledger_account_id, 'credit_minor' => 12500],
            ],
        ]);

        $this->assertInstanceOf(LedgerEntry::class, $entry);
        $this->assertSame(12500, $wallet->fresh()->balance_minor);
        $this->assertSame(2, $entry->lines->count());
    }

    public function test_replaying_an_idempotent_entry_does_not_double_credit(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->wallet($user, WalletType::Available);
        $systemAccount = app(LedgerService::class)->systemAccount('cash:usd', 'Cash clearing');
        $attributes = [
            'type' => LedgerEntryType::Deposit,
            'currency' => 'USD',
            'description' => 'Approved deposit',
            'idempotency_key' => 'deposit-'.$user->id.'-002',
            'lines' => [
                ['account_id' => $systemAccount->id, 'debit_minor' => 5000],
                ['account_id' => $wallet->ledger_account_id, 'credit_minor' => 5000],
            ],
        ];

        $first = app(LedgerService::class)->post($attributes);
        $second = app(LedgerService::class)->post($attributes);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, LedgerEntry::query()->where('idempotency_key', $attributes['idempotency_key'])->count());
        $this->assertSame(5000, $wallet->fresh()->balance_minor);
    }

    public function test_unbalanced_entries_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $user = User::factory()->create();
        $wallet = app(WalletService::class)->wallet($user, WalletType::Available);
        $systemAccount = app(LedgerService::class)->systemAccount('cash:usd', 'Cash clearing');

        app(LedgerService::class)->post([
            'type' => LedgerEntryType::Deposit,
            'currency' => 'USD',
            'description' => 'Invalid entry',
            'lines' => [
                ['account_id' => $systemAccount->id, 'debit_minor' => 1000],
                ['account_id' => $wallet->ledger_account_id, 'credit_minor' => 900],
            ],
        ]);
    }

    public function test_wallet_cannot_become_negative(): void
    {
        $this->expectException(InsufficientWalletBalance::class);

        $user = User::factory()->create();
        $wallet = app(WalletService::class)->wallet($user, WalletType::Available);
        $systemAccount = app(LedgerService::class)->systemAccount('cash:usd', 'Cash clearing');

        app(LedgerService::class)->post([
            'type' => LedgerEntryType::Withdrawal,
            'currency' => 'USD',
            'description' => 'Withdrawal beyond balance',
            'lines' => [
                ['account_id' => $wallet->ledger_account_id, 'debit_minor' => 1],
                ['account_id' => $systemAccount->id, 'credit_minor' => 1],
            ],
        ]);
    }
}
