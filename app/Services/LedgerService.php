<?php

namespace App\Services;

use App\Exceptions\InsufficientWalletBalance;
use App\LedgerAccountType;
use App\LedgerEntryStatus;
use App\LedgerEntryType;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class LedgerService
{
    /**
     * Post one balanced, immutable double-entry journal entry.
     *
     * @param array{
     *     type: LedgerEntryType|string,
     *     currency: string,
     *     description: string,
     *     lines: list<array{account_id: int, debit_minor?: int, credit_minor?: int, description?: string}>,
     *     idempotency_key?: string|null,
     *     actor_id?: int|null,
     *     metadata?: array<string, mixed>|null
     * } $attributes
     */
    public function post(array $attributes): LedgerEntry
    {
        $type = $attributes['type'] instanceof LedgerEntryType
            ? $attributes['type']
            : LedgerEntryType::from($attributes['type']);
        $currency = strtoupper($attributes['currency']);
        $lines = $attributes['lines'];
        $idempotencyKey = $attributes['idempotency_key'] ?? null;
        $payload = [
            'type' => $type->value,
            'currency' => $currency,
            'description' => $attributes['description'],
            'lines' => $lines,
            'actor_id' => $attributes['actor_id'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
        ];
        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($attributes, $type, $currency, $lines, $idempotencyKey, $payloadHash): LedgerEntry {
            if ($idempotencyKey !== null) {
                $existing = LedgerEntry::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    if ($existing->payload_hash !== $payloadHash) {
                        throw new LogicException('The idempotency key was already used with different data.');
                    }

                    return $existing->load('lines.account');
                }
            }

            $this->validateLines($lines, $currency);
            $accountIds = array_values(array_unique(array_map(
                fn (array $line): int => (int) $line['account_id'],
                $lines,
            )));
            $accounts = LedgerAccount::query()
                ->whereIn('id', $accountIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($accounts->count() !== count($accountIds)) {
                throw new InvalidArgumentException('Every ledger line must reference an existing account.');
            }

            $entry = LedgerEntry::query()->create([
                'reference' => Str::uuid()->toString(),
                'type' => $type,
                'status' => LedgerEntryStatus::Posted,
                'currency' => $currency,
                'description' => $attributes['description'],
                'idempotency_key' => $idempotencyKey,
                'actor_id' => $attributes['actor_id'] ?? null,
                'payload_hash' => $payloadHash,
                'metadata' => $attributes['metadata'] ?? null,
                'posted_at' => now(),
            ]);

            $wallets = Wallet::query()
                ->whereIn('ledger_account_id', $accountIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('ledger_account_id');

            foreach ($lines as $line) {
                $debitMinor = (int) ($line['debit_minor'] ?? 0);
                $creditMinor = (int) ($line['credit_minor'] ?? 0);
                LedgerLine::query()->create([
                    'ledger_entry_id' => $entry->getKey(),
                    'ledger_account_id' => $line['account_id'],
                    'debit_minor' => $debitMinor,
                    'credit_minor' => $creditMinor,
                    'description' => $line['description'] ?? null,
                ]);

                $wallet = $wallets->get($line['account_id']);
                if ($wallet instanceof Wallet) {
                    $newBalance = $wallet->balance_minor + $creditMinor - $debitMinor;

                    if ($newBalance < 0) {
                        throw new InsufficientWalletBalance($currency);
                    }

                    $wallet->update(['balance_minor' => $newBalance]);
                }
            }

            return $entry->load('lines.account');
        });
    }

    public function systemAccount(string $code, string $name, string $currency = 'USD', LedgerAccountType $type = LedgerAccountType::Asset): LedgerAccount
    {
        $currency = strtoupper($currency);

        return LedgerAccount::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'currency' => $currency, 'type' => $type],
        );
    }

    public function transfer(Wallet $from, Wallet $to, int $amountMinor, User $actor, ?string $idempotencyKey = null): LedgerEntry
    {
        if ($amountMinor <= 0 || $from->currency !== $to->currency) {
            throw new InvalidArgumentException('Transfers require a positive amount and matching currencies.');
        }

        return $this->post([
            'type' => LedgerEntryType::Transfer,
            'currency' => $from->currency,
            'description' => 'Wallet transfer',
            'idempotency_key' => $idempotencyKey,
            'actor_id' => $actor->getKey(),
            'lines' => [
                ['account_id' => $from->ledger_account_id, 'debit_minor' => $amountMinor],
                ['account_id' => $to->ledger_account_id, 'credit_minor' => $amountMinor],
            ],
        ]);
    }

    /**
     * @param  list<array{account_id: int, debit_minor?: int, credit_minor?: int, description?: string}>  $lines
     */
    private function validateLines(array $lines, string $currency): void
    {
        if ($lines === []) {
            throw new InvalidArgumentException('A ledger entry requires at least two lines.');
        }

        $debits = 0;
        $credits = 0;

        foreach ($lines as $line) {
            $debitMinor = (int) ($line['debit_minor'] ?? 0);
            $creditMinor = (int) ($line['credit_minor'] ?? 0);

            if (($debitMinor > 0) === ($creditMinor > 0) || $debitMinor < 0 || $creditMinor < 0) {
                throw new InvalidArgumentException('Each ledger line must contain either a positive debit or credit.');
            }

            $debits += $debitMinor;
            $credits += $creditMinor;
        }

        if ($debits !== $credits) {
            throw new InvalidArgumentException('Ledger debits and credits must balance.');
        }

        $accountIds = array_values(array_unique(array_map(
            fn (array $line): int => (int) $line['account_id'],
            $lines,
        )));
        $currencies = LedgerAccount::query()->whereIn('id', $accountIds)->pluck('currency');

        if ($currencies->contains(fn (string $accountCurrency): bool => $accountCurrency !== $currency)) {
            throw new InvalidArgumentException('All ledger accounts must use the entry currency.');
        }
    }
}
