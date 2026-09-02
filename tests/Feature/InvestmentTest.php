<?php

namespace Tests\Feature;

use App\InvestmentReturnStatus;
use App\LedgerAccountType;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Services\InvestmentService;
use App\Services\LedgerService;
use App\Services\WalletService;
use App\WalletType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_funding_an_investment_moves_principal_to_the_locked_wallet(): void
    {
        $user = User::factory()->create();
        $plan = InvestmentPlan::factory()->create(['minimum_amount_minor' => 10000, 'term_days' => 30]);
        $this->creditAvailableWallet($user, 25000);

        $investment = app(InvestmentService::class)->create($user, $plan, '100.00');

        $this->assertSame('active', $investment->status->value);
        $this->assertSame(15000, $user->wallets()->where('type', WalletType::Available)->value('balance_minor'));
        $this->assertSame(10000, $user->wallets()->where('type', WalletType::Locked)->value('balance_minor'));
        $this->assertSame(10000, $investment->principal_minor);
    }

    public function test_roi_requires_approval_and_release_before_it_reaches_available_balance(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'finance']);
        $plan = InvestmentPlan::factory()->create(['projected_rate_bps' => 3650, 'term_days' => 30]);
        $this->creditAvailableWallet($user, 20000);
        $investment = app(InvestmentService::class)->create($user, $plan, '100.00');
        $investmentReturn = app(InvestmentService::class)->calculateDueReturn($investment, CarbonImmutable::now()->addDay());

        $this->assertNotNull($investmentReturn);
        $this->assertSame(0, $user->wallets()->where('type', WalletType::Accrued)->value('balance_minor'));
        $this->assertSame(10000, $user->wallets()->where('type', WalletType::Locked)->value('balance_minor'));

        app(InvestmentService::class)->approveReturn($investmentReturn, $admin->id);
        $this->assertSame(100, $user->wallets()->where('type', WalletType::Accrued)->value('balance_minor'));
        $this->assertSame(10000, $user->wallets()->where('type', WalletType::Available)->value('balance_minor'));

        app(InvestmentService::class)->releaseReturn($investmentReturn, $admin->id);
        $this->assertSame(InvestmentReturnStatus::Released, $investmentReturn->fresh()->status);
        $this->assertSame(10100, $user->wallets()->where('type', WalletType::Available)->value('balance_minor'));
    }

    private function creditAvailableWallet(User $user, int $amountMinor): void
    {
        $wallet = app(WalletService::class)->wallet($user, WalletType::Available);
        $cashAccount = app(LedgerService::class)->systemAccount('cash:investment-test:'.$user->id, 'Investment test cash', 'USD', LedgerAccountType::Asset);

        app(LedgerService::class)->post([
            'type' => 'deposit',
            'currency' => 'USD',
            'description' => 'Test investment funding',
            'idempotency_key' => 'investment-test-funding-'.$user->id,
            'lines' => [
                ['account_id' => $cashAccount->id, 'debit_minor' => $amountMinor],
                ['account_id' => $wallet->ledger_account_id, 'credit_minor' => $amountMinor],
            ],
        ]);
    }
}
