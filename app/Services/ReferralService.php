<?php

namespace App\Services;

use App\LedgerAccountType;
use App\LedgerEntryType;
use App\Models\Investment;
use App\Models\ReferralCommission;
use App\Models\ReferralSetting;
use App\Models\User;
use App\ReferralCommissionStatus;
use App\WalletType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class ReferralService
{
    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly WalletService $walletService,
    ) {}

    public function ensureCode(User $user): User
    {
        if ($user->referral_code !== null) {
            return $user;
        }

        do {
            $code = Str::upper(Str::random(10));
        } while (User::query()->where('referral_code', $code)->exists());

        $user->update(['referral_code' => $code]);

        return $user->fresh();
    }

    public function attribute(User $user, ?string $code): User
    {
        $code = filled($code) ? Str::upper(trim($code)) : null;

        if ($code === null || $user->referred_by_id !== null) {
            return $user;
        }

        $referrer = User::query()->where('referral_code', $code)->first();

        if ($referrer === null || $referrer->is($user)) {
            return $user;
        }

        $user->update(['referred_by_id' => $referrer->getKey()]);

        return $user->fresh(['referredBy']);
    }

    public function createInvestmentCommission(Investment $investment): ?ReferralCommission
    {
        $settings = $this->settings();
        $investment->loadMissing('user');

        if (! $settings->is_enabled || $investment->user->referred_by_id === null || $investment->principal_minor < $settings->minimum_qualifying_investment_minor || $settings->investment_commission_bps === 0) {
            return null;
        }

        $commissionAmountMinor = intdiv(($investment->principal_minor * $settings->investment_commission_bps) + 5000, 10000);

        if ($commissionAmountMinor <= 0) {
            return null;
        }

        $eventKey = "investment:{$investment->getKey()}:commission";
        $existing = ReferralCommission::query()->where('event_key', $eventKey)->first();

        if ($existing !== null) {
            return $existing;
        }

        return ReferralCommission::query()->create([
            'referrer_id' => $investment->user->referred_by_id,
            'referred_user_id' => $investment->user->getKey(),
            'investment_id' => $investment->getKey(),
            'reference' => (string) Str::uuid(),
            'event_key' => $eventKey,
            'currency' => $investment->currency,
            'qualifying_amount_minor' => $investment->principal_minor,
            'commission_amount_minor' => $commissionAmountMinor,
            'commission_rate_bps' => $settings->investment_commission_bps,
            'trigger' => 'investment_funded',
            'status' => ReferralCommissionStatus::Pending,
            'hold_until' => now()->addDays($settings->hold_days),
            'metadata' => ['settings_id' => $settings->getKey(), 'investment_reference' => $investment->reference],
        ]);
    }

    public function approve(ReferralCommission $commission, int $actorId, ?string $notes = null): ReferralCommission
    {
        return DB::transaction(function () use ($commission, $actorId, $notes): ReferralCommission {
            $commission = ReferralCommission::query()->with('referrer')->lockForUpdate()->findOrFail($commission->getKey());

            if (! in_array($commission->status->value, [ReferralCommissionStatus::Pending->value, ReferralCommissionStatus::Held->value], true)) {
                throw new LogicException('Only pending or held commissions can be approved.');
            }

            if ($commission->hold_until?->isFuture()) {
                throw new LogicException('This commission is still within its anti-abuse hold period.');
            }

            $referralWallet = $this->walletService->wallet($commission->referrer, WalletType::Referral, $commission->currency);
            $expense = $this->ledgerService->systemAccount("expense:referral-commissions:{$commission->currency}", 'Referral commission expense', $commission->currency, LedgerAccountType::Expense);
            $entry = $this->ledgerService->post([
                'type' => LedgerEntryType::Referral,
                'currency' => $commission->currency,
                'description' => 'Referral commission approved',
                'idempotency_key' => "referral:approve:{$commission->getKey()}",
                'actor_id' => $actorId,
                'metadata' => ['commission_reference' => $commission->reference],
                'lines' => [
                    ['account_id' => $expense->getKey(), 'debit_minor' => $commission->commission_amount_minor],
                    ['account_id' => $referralWallet->ledger_account_id, 'credit_minor' => $commission->commission_amount_minor],
                ],
            ]);
            $commission->update([
                'status' => ReferralCommissionStatus::Approved,
                'approved_by' => $actorId,
                'approved_at' => now(),
                'ledger_entry_id' => $entry->getKey(),
                'review_notes' => $notes,
            ]);

            return $commission->fresh(['referrer', 'referredUser']);
        });
    }

    public function release(ReferralCommission $commission, int $actorId): ReferralCommission
    {
        return DB::transaction(function () use ($commission, $actorId): ReferralCommission {
            $commission = ReferralCommission::query()->with('referrer')->lockForUpdate()->findOrFail($commission->getKey());

            if ($commission->status->value !== ReferralCommissionStatus::Approved->value) {
                throw new LogicException('Only approved commissions can be released.');
            }

            $referralWallet = $this->walletService->wallet($commission->referrer, WalletType::Referral, $commission->currency);
            $availableWallet = $this->walletService->wallet($commission->referrer, WalletType::Available, $commission->currency);
            $this->ledgerService->post([
                'type' => LedgerEntryType::Referral,
                'currency' => $commission->currency,
                'description' => 'Referral commission released to available wallet',
                'idempotency_key' => "referral:release:{$commission->getKey()}",
                'actor_id' => $actorId,
                'metadata' => ['commission_reference' => $commission->reference],
                'lines' => [
                    ['account_id' => $referralWallet->ledger_account_id, 'debit_minor' => $commission->commission_amount_minor],
                    ['account_id' => $availableWallet->ledger_account_id, 'credit_minor' => $commission->commission_amount_minor],
                ],
            ]);
            $commission->update(['status' => ReferralCommissionStatus::Released, 'released_at' => now()]);

            return $commission->fresh(['referrer', 'referredUser']);
        });
    }

    public function clawBack(ReferralCommission $commission, int $actorId, string $reason): ReferralCommission
    {
        return DB::transaction(function () use ($commission, $actorId, $reason): ReferralCommission {
            $commission = ReferralCommission::query()->with('referrer')->lockForUpdate()->findOrFail($commission->getKey());

            if (in_array($commission->status->value, [ReferralCommissionStatus::Pending->value, ReferralCommissionStatus::Held->value], true)) {
                $commission->update(['status' => ReferralCommissionStatus::ClawedBack, 'clawed_back_at' => now(), 'review_notes' => $reason, 'approved_by' => $actorId]);

                return $commission->fresh(['referrer', 'referredUser']);
            }

            if (! in_array($commission->status->value, [ReferralCommissionStatus::Approved->value, ReferralCommissionStatus::Released->value], true)) {
                throw new LogicException('This commission cannot be clawed back in its current state.');
            }

            $walletType = $commission->status->value === ReferralCommissionStatus::Released->value ? WalletType::Available : WalletType::Referral;
            $wallet = $this->walletService->wallet($commission->referrer, $walletType, $commission->currency);
            $clawbackAccount = $this->ledgerService->systemAccount("revenue:referral-clawbacks:{$commission->currency}", 'Referral clawback recovery', $commission->currency, LedgerAccountType::Revenue);
            $this->ledgerService->post([
                'type' => LedgerEntryType::Referral,
                'currency' => $commission->currency,
                'description' => 'Referral commission clawed back',
                'idempotency_key' => "referral:clawback:{$commission->getKey()}",
                'actor_id' => $actorId,
                'metadata' => ['commission_reference' => $commission->reference, 'reason' => $reason],
                'lines' => [
                    ['account_id' => $wallet->ledger_account_id, 'debit_minor' => $commission->commission_amount_minor],
                    ['account_id' => $clawbackAccount->getKey(), 'credit_minor' => $commission->commission_amount_minor],
                ],
            ]);
            $commission->update(['status' => ReferralCommissionStatus::ClawedBack, 'clawed_back_at' => now(), 'review_notes' => $reason, 'approved_by' => $actorId]);

            return $commission->fresh(['referrer', 'referredUser']);
        });
    }

    public function settings(): ReferralSetting
    {
        return ReferralSetting::query()->firstOrCreate(['id' => 1], [
            'investment_commission_bps' => 500,
            'hold_days' => 30,
            'minimum_qualifying_investment_minor' => 10000,
            'is_enabled' => false,
        ]);
    }
}
