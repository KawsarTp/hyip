<?php

namespace App\Services;

use App\LedgerAccountType;
use App\LedgerEntryType;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use App\WalletType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class WithdrawalService
{
    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly WalletService $walletService,
    ) {}

    /**
     * Reserve available funds and create a manual withdrawal request.
     *
     * @param  array<string, string|null>  $destinationDetails
     */
    public function create(User $user, string $amount, string $currency, WithdrawalMethod $method, array $destinationDetails, ?string $idempotencyKey = null): Withdrawal
    {
        $amountMinor = $this->amountToMinor($amount);
        $currency = strtoupper($currency);
        $idempotencyKey ??= (string) Str::uuid();

        if (! $method->is_enabled || $method->currency !== $currency) {
            throw new InvalidArgumentException('The selected withdrawal method is not available for this currency.');
        }

        if ($amountMinor < $method->minimum_amount_minor || ($method->maximum_amount_minor !== null && $amountMinor > $method->maximum_amount_minor)) {
            throw new InvalidArgumentException('The withdrawal amount is outside this method’s allowed limits.');
        }

        [$feeMinor, $netMinor] = $this->quote($amountMinor, $method);

        return DB::transaction(function () use ($user, $amountMinor, $currency, $method, $destinationDetails, $idempotencyKey, $feeMinor, $netMinor): Withdrawal {
            $existing = Withdrawal::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();

            if ($existing !== null) {
                if ($existing->user_id !== $user->getKey() || $existing->withdrawal_method_id !== $method->getKey() || $existing->gross_amount_minor !== $amountMinor || $existing->currency !== $currency) {
                    throw new LogicException('The withdrawal idempotency key was already used with different data.');
                }

                return $existing;
            }

            $withdrawal = Withdrawal::query()->create([
                'user_id' => $user->getKey(),
                'withdrawal_method_id' => $method->getKey(),
                'reference' => (string) Str::uuid(),
                'idempotency_key' => $idempotencyKey,
                'currency' => $currency,
                'gross_amount_minor' => $amountMinor,
                'fee_amount_minor' => $feeMinor,
                'net_amount_minor' => $netMinor,
                'status' => 'pending',
                'destination_details' => $destinationDetails,
                'reserved_at' => now(),
                'metadata' => [
                    'method_name' => $method->name,
                    'fee_fixed_minor' => $method->fee_fixed_minor,
                    'fee_percent_bps' => $method->fee_percent_bps,
                ],
            ]);

            $available = $this->walletService->wallet($user, WalletType::Available, $currency);
            $pending = $this->walletService->wallet($user, WalletType::Pending, $currency);
            $this->ledgerService->transfer($available, $pending, $amountMinor, $user, "withdrawal:reserve:{$withdrawal->getKey()}");

            return $withdrawal;
        });
    }

    /**
     * @return array{fee_minor: int, net_minor: int}
     */
    public function quote(int $grossMinor, WithdrawalMethod $method): array
    {
        $feeMinor = $method->fee_fixed_minor + intdiv(($grossMinor * $method->fee_percent_bps) + 5000, 10000);
        $netMinor = $grossMinor - $feeMinor;

        if ($netMinor <= 0) {
            throw new InvalidArgumentException('The withdrawal fee must be lower than the requested amount.');
        }

        return ['fee_minor' => $feeMinor, 'net_minor' => $netMinor];
    }

    public function approve(Withdrawal $withdrawal, int $actorId, ?string $notes = null): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $actorId, $notes): Withdrawal {
            $withdrawal = Withdrawal::query()->lockForUpdate()->findOrFail($withdrawal->getKey());

            if ($withdrawal->status->value !== 'pending') {
                throw new LogicException('Only pending withdrawals can be approved.');
            }

            $withdrawal->update([
                'status' => 'approved',
                'reviewed_by' => $actorId,
                'reviewed_at' => now(),
                'metadata' => array_merge($withdrawal->metadata ?? [], ['review_notes' => $notes]),
            ]);

            return $withdrawal->fresh(['user', 'withdrawalMethod']);
        });
    }

    public function reject(Withdrawal $withdrawal, string $reason, int $actorId): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $reason, $actorId): Withdrawal {
            $withdrawal = Withdrawal::query()->lockForUpdate()->findOrFail($withdrawal->getKey());

            if (! in_array($withdrawal->status->value, ['pending', 'approved'], true)) {
                throw new LogicException('Only pending or approved withdrawals can be rejected.');
            }

            $this->releaseReservation($withdrawal, $actorId, 'Withdrawal rejected; reserved funds released.');
            $withdrawal->update([
                'status' => 'rejected',
                'reviewed_by' => $actorId,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $withdrawal->fresh(['user', 'withdrawalMethod']);
        });
    }

    public function complete(Withdrawal $withdrawal, string $externalReference, int $actorId): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $externalReference, $actorId): Withdrawal {
            $withdrawal = Withdrawal::query()->lockForUpdate()->findOrFail($withdrawal->getKey());

            if (! in_array($withdrawal->status->value, ['pending', 'approved'], true)) {
                throw new LogicException('Only pending or approved withdrawals can be completed.');
            }

            $pending = $this->walletService->wallet($withdrawal->user, WalletType::Pending, $withdrawal->currency);
            $payoutAccount = $this->ledgerService->systemAccount(
                "payout:manual:{$withdrawal->currency}",
                'Manual payout clearing',
                $withdrawal->currency,
                LedgerAccountType::Asset,
            );
            $feeAccount = $this->ledgerService->systemAccount(
                "revenue:withdrawal-fees:{$withdrawal->currency}",
                'Withdrawal fee revenue',
                $withdrawal->currency,
                LedgerAccountType::Revenue,
            );
            $lines = [
                ['account_id' => $pending->ledger_account_id, 'debit_minor' => $withdrawal->gross_amount_minor, 'description' => 'Release withdrawal reservation'],
                ['account_id' => $payoutAccount->getKey(), 'credit_minor' => $withdrawal->net_amount_minor, 'description' => 'Manual payout sent to destination'],
            ];

            if ($withdrawal->fee_amount_minor > 0) {
                $lines[] = ['account_id' => $feeAccount->getKey(), 'credit_minor' => $withdrawal->fee_amount_minor, 'description' => 'Withdrawal processing fee'];
            }

            $this->ledgerService->post([
                'type' => LedgerEntryType::Withdrawal,
                'currency' => $withdrawal->currency,
                'description' => 'Manual withdrawal completed',
                'idempotency_key' => "withdrawal:complete:{$withdrawal->getKey()}",
                'actor_id' => $actorId,
                'metadata' => ['withdrawal_reference' => $withdrawal->reference, 'external_reference' => $externalReference],
                'lines' => $lines,
            ]);

            $withdrawal->update([
                'status' => 'completed',
                'external_reference' => $externalReference,
                'reviewed_by' => $actorId,
                'reviewed_at' => $withdrawal->reviewed_at ?? now(),
                'completed_at' => now(),
            ]);

            return $withdrawal->fresh(['user', 'withdrawalMethod']);
        });
    }

    public function cancel(Withdrawal $withdrawal, User $user): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $user): Withdrawal {
            $withdrawal = Withdrawal::query()->lockForUpdate()->findOrFail($withdrawal->getKey());

            if ($withdrawal->user_id !== $user->getKey()) {
                abort(403);
            }

            if ($withdrawal->status->value !== 'pending') {
                throw new LogicException('Only pending withdrawals can be cancelled.');
            }

            $this->releaseReservation($withdrawal, $user->getKey(), 'Withdrawal cancelled; reserved funds released.');
            $withdrawal->update(['status' => 'cancelled', 'reviewed_at' => now()]);

            return $withdrawal->fresh(['user', 'withdrawalMethod']);
        });
    }

    private function releaseReservation(Withdrawal $withdrawal, int $actorId, string $description): void
    {
        $pending = $this->walletService->wallet($withdrawal->user, WalletType::Pending, $withdrawal->currency);
        $available = $this->walletService->wallet($withdrawal->user, WalletType::Available, $withdrawal->currency);
        $this->ledgerService->post([
            'type' => LedgerEntryType::Withdrawal,
            'currency' => $withdrawal->currency,
            'description' => $description,
            'idempotency_key' => "withdrawal:release:{$withdrawal->getKey()}",
            'actor_id' => $actorId,
            'metadata' => ['withdrawal_reference' => $withdrawal->reference],
            'lines' => [
                ['account_id' => $pending->ledger_account_id, 'debit_minor' => $withdrawal->gross_amount_minor],
                ['account_id' => $available->ledger_account_id, 'credit_minor' => $withdrawal->gross_amount_minor],
            ],
        ]);
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
