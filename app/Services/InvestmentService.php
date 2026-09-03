<?php

namespace App\Services;

use App\InvestmentReturnStatus;
use App\InvestmentStatus;
use App\LedgerAccountType;
use App\LedgerEntryType;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\InvestmentReturn;
use App\Models\User;
use App\PlanStatus;
use App\WalletType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class InvestmentService
{
    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly WalletService $walletService,
        private readonly ReferralService $referralService,
        private readonly EmailNotificationService $emailNotificationService,
    ) {}

    public function create(User $user, InvestmentPlan $plan, string $amount): Investment
    {
        $amountMinor = $this->amountToMinor($amount);
        $currency = strtoupper($plan->currency);

        if ($plan->status->value !== PlanStatus::Published->value || $plan->effective_at?->isFuture()) {
            throw new LogicException('This investment plan is not currently available.');
        }

        if ($amountMinor < $plan->minimum_amount_minor || ($plan->maximum_amount_minor !== null && $amountMinor > $plan->maximum_amount_minor)) {
            throw new InvalidArgumentException('The investment amount is outside this plan’s limits.');
        }

        return DB::transaction(function () use ($user, $plan, $amountMinor, $currency): Investment {
            $plan = InvestmentPlan::query()->lockForUpdate()->findOrFail($plan->getKey());

            if ($plan->status->value !== PlanStatus::Published->value || $plan->effective_at?->isFuture()) {
                throw new LogicException('This investment plan is not currently available.');
            }

            if ($plan->capacity_minor !== null && $plan->current_invested_minor + $amountMinor > $plan->capacity_minor) {
                throw new LogicException('This plan does not have enough remaining capacity.');
            }

            $startAt = CarbonImmutable::now();
            $maturityAt = $startAt->addDays($plan->term_days);
            $investment = Investment::query()->create([
                'user_id' => $user->getKey(),
                'investment_plan_id' => $plan->getKey(),
                'reference' => (string) Str::uuid(),
                'plan_version' => $plan->version,
                'currency' => $currency,
                'principal_minor' => $amountMinor,
                'projected_return_minor' => $this->projectedReturn($amountMinor, $plan),
                'accrued_return_minor' => 0,
                'released_return_minor' => 0,
                'status' => InvestmentStatus::Active,
                'start_at' => $startAt,
                'maturity_at' => $maturityAt,
                'risk_acknowledged_at' => now(),
                'terms_snapshot' => [
                    'name' => $plan->name,
                    'version' => $plan->version,
                    'currency' => $plan->currency,
                    'minimum_amount_minor' => $plan->minimum_amount_minor,
                    'maximum_amount_minor' => $plan->maximum_amount_minor,
                    'term_days' => $plan->term_days,
                    'lock_days' => $plan->lock_days,
                    'projected_rate_bps' => $plan->projected_rate_bps,
                    'rate_type' => $plan->rate_type,
                    'calculation_frequency' => $plan->calculation_frequency,
                    'day_count_basis' => $plan->day_count_basis,
                    'compounding' => $plan->compounding,
                    'risk_rating' => $plan->risk_rating,
                ],
            ]);

            $available = $this->walletService->wallet($user, WalletType::Available, $currency);
            $locked = $this->walletService->wallet($user, WalletType::Locked, $currency);
            $this->ledgerService->transfer($available, $locked, $amountMinor, $user, "investment:fund:{$investment->getKey()}");
            $plan->increment('current_invested_minor', $amountMinor);
            $this->referralService->createInvestmentCommission($investment);
            $this->emailNotificationService->queueForUser(
                $user,
                'investment_activated',
                [
                    'amount' => $this->emailNotificationService->formatAmount($amountMinor, $currency),
                    'currency' => $currency,
                    'reference' => $investment->reference,
                    'plan_name' => $plan->name,
                    'maturity_at' => $maturityAt->toDateString(),
                ],
                "investment:{$investment->getKey()}:activated",
            );

            return $investment->load('investmentPlan');
        });
    }

    public function calculateDueReturn(Investment $investment, ?CarbonImmutable $asOf = null): ?InvestmentReturn
    {
        return DB::transaction(function () use ($investment, $asOf): ?InvestmentReturn {
            $investment = Investment::query()->with('investmentPlan')->lockForUpdate()->findOrFail($investment->getKey());

            if ($investment->status->value !== InvestmentStatus::Active->value) {
                return null;
            }

            $asOf ??= CarbonImmutable::now();
            $periodStart = $investment->last_calculated_at?->toImmutable() ?? $investment->start_at->toImmutable();
            $maturityAt = $investment->maturity_at->toImmutable();
            $periodEnd = $this->periodEnd($investment, $periodStart, $maturityAt, $asOf);

            if ($periodEnd === null || $periodEnd->lessThanOrEqualTo($periodStart)) {
                return null;
            }

            $periodKey = $periodStart->format('Y-m-d H:i:s').'_'.$periodEnd->format('Y-m-d H:i:s');
            $existing = InvestmentReturn::query()->where('investment_id', $investment->getKey())->where('period_key', $periodKey)->first();

            if ($existing !== null) {
                return $existing;
            }

            $eligibleDays = max(1, $periodStart->diffInDays($periodEnd));
            $returnAmountMinor = $this->returnAmount($investment->principal_minor, $investment->investmentPlan, $eligibleDays, $periodEnd->equalTo($maturityAt));
            $investmentReturn = InvestmentReturn::query()->create([
                'investment_id' => $investment->getKey(),
                'period_key' => $periodKey,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'opening_principal_minor' => $investment->principal_minor,
                'eligible_days' => $eligibleDays,
                'rate_bps' => $investment->investmentPlan->projected_rate_bps,
                'return_amount_minor' => $returnAmountMinor,
                'status' => InvestmentReturnStatus::Accrued,
                'formula_version' => 'simple-return-v1',
                'calculation_snapshot' => [
                    'plan_id' => $investment->investmentPlan->getKey(),
                    'plan_version' => $investment->plan_version,
                    'rate_type' => $investment->investmentPlan->rate_type,
                    'rate_bps' => $investment->investmentPlan->projected_rate_bps,
                    'principal_minor' => $investment->principal_minor,
                    'eligible_days' => $eligibleDays,
                    'day_count_basis' => $investment->investmentPlan->day_count_basis,
                    'formula' => $investment->investmentPlan->rate_type === 'term_simple'
                        ? 'principal * term_rate'
                        : 'principal * annual_rate * eligible_days / day_count_basis',
                    'calculated_at' => now()->toIso8601String(),
                ],
            ]);
            $investment->update([
                'last_calculated_at' => $periodEnd,
                'accrued_return_minor' => $investment->accrued_return_minor + $returnAmountMinor,
            ]);
            $this->emailNotificationService->queueForUser(
                $investment->user,
                'return_posted',
                [
                    'amount' => $this->emailNotificationService->formatAmount($returnAmountMinor, $investment->currency),
                    'currency' => $investment->currency,
                    'reference' => $investment->reference,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                "investment-return:{$investmentReturn->getKey()}:posted",
            );

            return $investmentReturn;
        });
    }

    public function approveReturn(InvestmentReturn $investmentReturn, int $actorId): InvestmentReturn
    {
        return DB::transaction(function () use ($investmentReturn, $actorId): InvestmentReturn {
            $investmentReturn = InvestmentReturn::query()->with('investment.user')->lockForUpdate()->findOrFail($investmentReturn->getKey());

            if ($investmentReturn->status->value !== InvestmentReturnStatus::Accrued->value) {
                throw new LogicException('Only accrued returns can be approved.');
            }

            $accrued = $this->walletService->wallet($investmentReturn->investment->user, WalletType::Accrued, $investmentReturn->investment->currency);
            $expense = $this->ledgerService->systemAccount("expense:projected-returns:{$investmentReturn->investment->currency}", 'Projected return expense', $investmentReturn->investment->currency, LedgerAccountType::Expense);
            $entry = $this->ledgerService->post([
                'type' => LedgerEntryType::Return,
                'currency' => $investmentReturn->investment->currency,
                'description' => 'Return approved for investment',
                'idempotency_key' => "investment:return:approve:{$investmentReturn->getKey()}",
                'actor_id' => $actorId,
                'metadata' => ['investment_return_id' => $investmentReturn->getKey()],
                'lines' => [
                    ['account_id' => $expense->getKey(), 'debit_minor' => $investmentReturn->return_amount_minor],
                    ['account_id' => $accrued->ledger_account_id, 'credit_minor' => $investmentReturn->return_amount_minor],
                ],
            ]);
            $investmentReturn->update(['status' => InvestmentReturnStatus::Approved, 'approved_by' => $actorId, 'approved_at' => now(), 'ledger_entry_id' => $entry->getKey()]);

            return $investmentReturn->fresh(['investment', 'approver']);
        });
    }

    public function releaseReturn(InvestmentReturn $investmentReturn, int $actorId): InvestmentReturn
    {
        return DB::transaction(function () use ($investmentReturn, $actorId): InvestmentReturn {
            $investmentReturn = InvestmentReturn::query()->with('investment.user')->lockForUpdate()->findOrFail($investmentReturn->getKey());

            if ($investmentReturn->status->value !== InvestmentReturnStatus::Approved->value) {
                throw new LogicException('Only approved returns can be released.');
            }

            $investment = $investmentReturn->investment;
            $accrued = $this->walletService->wallet($investment->user, WalletType::Accrued, $investment->currency);
            $available = $this->walletService->wallet($investment->user, WalletType::Available, $investment->currency);
            $this->ledgerService->post([
                'type' => LedgerEntryType::Return,
                'currency' => $investment->currency,
                'description' => 'Return released to available wallet',
                'idempotency_key' => "investment:return:release:{$investmentReturn->getKey()}",
                'actor_id' => $actorId,
                'metadata' => ['investment_return_id' => $investmentReturn->getKey()],
                'lines' => [
                    ['account_id' => $accrued->ledger_account_id, 'debit_minor' => $investmentReturn->return_amount_minor],
                    ['account_id' => $available->ledger_account_id, 'credit_minor' => $investmentReturn->return_amount_minor],
                ],
            ]);
            $investmentReturn->update(['status' => InvestmentReturnStatus::Released, 'released_at' => now()]);
            $investment->increment('released_return_minor', $investmentReturn->return_amount_minor);

            return $investmentReturn->fresh(['investment']);
        });
    }

    public function mature(Investment $investment, int $actorId): Investment
    {
        return DB::transaction(function () use ($investment, $actorId): Investment {
            $investment = Investment::query()->lockForUpdate()->findOrFail($investment->getKey());

            if ($investment->status->value !== InvestmentStatus::Active->value || $investment->maturity_at->isFuture()) {
                throw new LogicException('This investment is not ready to mature.');
            }

            $investment->update(['status' => InvestmentStatus::Matured, 'matured_at' => now(), 'metadata' => array_merge($investment->metadata ?? [], ['matured_by' => $actorId])]);

            return $investment->fresh(['user', 'investmentPlan', 'returns']);
        });
    }

    public function complete(Investment $investment, int $actorId): Investment
    {
        return DB::transaction(function () use ($investment, $actorId): Investment {
            $investment = Investment::query()->with('investmentPlan')->lockForUpdate()->findOrFail($investment->getKey());

            if ($investment->status->value !== InvestmentStatus::Matured->value) {
                throw new LogicException('Only matured investments can be completed.');
            }

            if ($investment->returns()->whereIn('status', [InvestmentReturnStatus::Accrued->value, InvestmentReturnStatus::Approved->value])->exists()) {
                throw new LogicException('Approve and release every calculated return before completing this investment.');
            }

            $locked = $this->walletService->wallet($investment->user, WalletType::Locked, $investment->currency);
            $available = $this->walletService->wallet($investment->user, WalletType::Available, $investment->currency);
            $this->ledgerService->transfer($locked, $available, $investment->principal_minor, $investment->user, "investment:complete:{$investment->getKey()}");
            $investment->investmentPlan->decrement('current_invested_minor', $investment->principal_minor);
            $investment->update(['status' => InvestmentStatus::Completed, 'completed_at' => now(), 'metadata' => array_merge($investment->metadata ?? [], ['completed_by' => $actorId])]);

            return $investment->fresh(['user', 'investmentPlan', 'returns']);
        });
    }

    private function periodEnd(Investment $investment, CarbonImmutable $periodStart, CarbonImmutable $maturityAt, CarbonImmutable $asOf): ?CarbonImmutable
    {
        $frequency = $investment->investmentPlan->calculation_frequency;

        if ($investment->investmentPlan->rate_type === 'term_simple' || $frequency === 'maturity') {
            return $asOf->greaterThanOrEqualTo($maturityAt) ? $maturityAt : null;
        }

        $nextBoundary = match ($frequency) {
            'monthly' => $periodStart->addMonth(),
            default => $periodStart->addDay(),
        };

        if ($asOf->lessThan($nextBoundary)) {
            return null;
        }

        return $nextBoundary->lessThan($maturityAt) ? $nextBoundary : $maturityAt;
    }

    private function projectedReturn(int $principalMinor, InvestmentPlan $plan): int
    {
        if ($plan->rate_type === 'term_simple') {
            return intdiv(($principalMinor * $plan->projected_rate_bps) + 5000, 10000);
        }

        $denominator = $plan->day_count_basis * 10000;

        return intdiv(($principalMinor * $plan->projected_rate_bps * $plan->term_days) + intdiv($denominator, 2), $denominator);
    }

    private function returnAmount(int $principalMinor, InvestmentPlan $plan, int $eligibleDays, bool $isMaturity): int
    {
        if ($plan->rate_type === 'term_simple' && ! $isMaturity) {
            return 0;
        }

        if ($plan->rate_type === 'term_simple') {
            return intdiv(($principalMinor * $plan->projected_rate_bps) + 5000, 10000);
        }

        $denominator = $plan->day_count_basis * 10000;

        return intdiv(($principalMinor * $plan->projected_rate_bps * $eligibleDays) + intdiv($denominator, 2), $denominator);
    }

    private function amountToMinor(string $amount): int
    {
        if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Amount must be a positive value with up to two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $minor = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        if ($minor <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return $minor;
    }
}
