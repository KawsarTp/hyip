<?php

namespace App\Services;

use App\DepositStatus;
use App\LedgerAccountType;
use App\LedgerEntryType;
use App\Models\Deposit;
use App\Models\DepositEvent;
use App\Models\PaymentGateway;
use App\Models\PaymentIntent;
use App\Models\User;
use App\PaymentIntentStatus;
use App\PaymentMethod;
use App\WalletType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class DepositService
{
    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly WalletService $walletService,
    ) {}

    public function create(User $user, string $amount, string $currency, PaymentGateway $gateway, ?string $idempotencyKey = null): Deposit
    {
        $amountMinor = $this->amountToMinor($amount);
        $currency = strtoupper($currency);
        $method = PaymentMethod::tryFrom($gateway->method);

        if (! $gateway->is_enabled || $method === null) {
            throw new InvalidArgumentException('The selected payment gateway is not valid.');
        }

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Currency must be a three-letter ISO code.');
        }

        $expectedMethods = [
            'stripe' => 'card',
            'paypal' => 'paypal',
            'crypto' => 'crypto',
            'bank_transfer' => 'bank_transfer',
        ];

        if (($expectedMethods[$gateway->driver] ?? null) !== $method->value) {
            throw new InvalidArgumentException('The gateway driver and payment method do not match.');
        }

        $bankAccount = $gateway->driver === 'bank_transfer'
            ? $gateway->bankAccounts->first(fn ($account): bool => $account->currency === $currency)
            : null;

        if ($gateway->driver === 'bank_transfer' && $bankAccount === null) {
            throw new LogicException('This bank gateway has no enabled account for the selected currency.');
        }

        return DB::transaction(function () use ($user, $amountMinor, $currency, $method, $gateway, $bankAccount, $idempotencyKey): Deposit {
            if ($idempotencyKey !== null) {
                $existing = Deposit::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    if ($existing->amount_minor !== $amountMinor || $existing->currency !== $currency || $existing->method !== $method->value || $existing->payment_gateway_id !== $gateway->getKey()) {
                        throw new LogicException('The deposit idempotency key was already used with different data.');
                    }

                    return $existing->load('paymentIntent');
                }
            }

            $paymentIntent = PaymentIntent::query()->create([
                'user_id' => $user->getKey(),
                'payment_gateway_id' => $gateway->getKey(),
                'reference' => (string) Str::uuid(),
                'provider' => $gateway->driver,
                'method' => $method,
                'currency' => $currency,
                'amount_minor' => $amountMinor,
                'status' => PaymentIntentStatus::Created,
                'idempotency_key' => $idempotencyKey,
                'metadata' => ['gateway_name' => $gateway->name, 'gateway_settings' => $gateway->settings],
                'expires_at' => now()->addDay(),
            ]);

            return Deposit::query()->create([
                'user_id' => $user->getKey(),
                'payment_intent_id' => $paymentIntent->getKey(),
                'reference' => (string) Str::uuid(),
                'idempotency_key' => $idempotencyKey,
                'method' => $method,
                'currency' => $currency,
                'amount_minor' => $amountMinor,
                'status' => DepositStatus::Pending,
                'metadata' => $bankAccount === null ? ['gateway_name' => $gateway->name] : [
                    'gateway_name' => $gateway->name,
                    'bank_account' => [
                        'label' => $bankAccount->label,
                        'bank_name' => $bankAccount->bank_name,
                        'account_name' => $bankAccount->account_name,
                        'account_number' => $bankAccount->account_number,
                        'iban' => $bankAccount->iban,
                        'swift_code' => $bankAccount->swift_code,
                        'branch' => $bankAccount->branch,
                        'instructions' => $bankAccount->instructions,
                    ],
                ],
            ]);
        });
    }

    public function settle(Deposit $deposit, string $externalReference, ?int $actorId = null): Deposit
    {
        return DB::transaction(function () use ($deposit, $externalReference, $actorId): Deposit {
            $deposit = Deposit::query()->lockForUpdate()->findOrFail($deposit->getKey());

            return $this->settleLocked($deposit, $externalReference, $actorId);
        });
    }

    public function reject(Deposit $deposit, string $reason, ?int $actorId = null): Deposit
    {
        return DB::transaction(function () use ($deposit, $reason, $actorId): Deposit {
            $deposit = Deposit::query()->lockForUpdate()->findOrFail($deposit->getKey());

            if ($deposit->status === DepositStatus::Completed) {
                throw new LogicException('A completed deposit cannot be rejected.');
            }

            $deposit->update([
                'status' => DepositStatus::Rejected,
                'rejection_reason' => $reason,
                'metadata' => array_merge($deposit->metadata ?? [], ['reviewed_by' => $actorId]),
            ]);
            $deposit->paymentIntent?->update([
                'status' => PaymentIntentStatus::Failed,
                'failure_reason' => $reason,
            ]);

            return $deposit->fresh(['paymentIntent']);
        });
    }

    /**
     * Process a signed provider event exactly once.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processWebhook(array $payload, string $provider = 'generic'): DepositEvent
    {
        return DB::transaction(function () use ($payload, $provider): DepositEvent {
            $existingEvent = DepositEvent::query()
                ->where('provider', $provider)
                ->where('provider_event_id', $payload['event_id'])
                ->lockForUpdate()
                ->first();

            if ($existingEvent !== null) {
                return $existingEvent;
            }

            $paymentIntent = PaymentIntent::query()
                ->where('reference', $payload['payment_intent_reference'])
                ->lockForUpdate()
                ->first();

            if ($paymentIntent === null) {
                throw (new ModelNotFoundException)->setModel(PaymentIntent::class, [$payload['payment_intent_reference']]);
            }

            $deposit = $paymentIntent->deposit()->lockForUpdate()->firstOrFail();
            $event = DepositEvent::query()->create([
                'payment_intent_id' => $paymentIntent->getKey(),
                'deposit_id' => $deposit->getKey(),
                'provider' => $provider,
                'provider_event_id' => $payload['event_id'],
                'event_type' => $payload['event_type'],
                'payload' => $payload,
                'status' => 'received',
            ]);

            if (! empty($payload['provider_intent_id'])) {
                $paymentIntent->update(['provider_intent_id' => $payload['provider_intent_id']]);
            }

            if ((int) $payload['amount_minor'] !== $paymentIntent->amount_minor || strtoupper($payload['currency']) !== $paymentIntent->currency) {
                $event->update(['status' => 'failed', 'error_message' => 'Amount or currency does not match the payment intent.']);

                return $event->fresh();
            }

            if ($payload['event_type'] === 'payment.succeeded') {
                if ($deposit->status !== DepositStatus::Pending) {
                    $event->update(['status' => 'ignored', 'processed_at' => now(), 'error_message' => 'Only pending deposits can be settled.']);

                    return $event->fresh();
                }

                $this->settleLocked($deposit, (string) ($payload['external_reference'] ?? $payload['event_id']));
            } else {
                $reason = (string) ($payload['failure_reason'] ?? 'Payment provider reported a failure.');
                if ($deposit->status === DepositStatus::Completed) {
                    $event->update(['status' => 'ignored', 'processed_at' => now(), 'error_message' => 'A completed deposit cannot be failed by a later event.']);

                    return $event->fresh();
                }

                $deposit->update(['status' => DepositStatus::Failed, 'rejection_reason' => $reason]);
                $paymentIntent->update(['status' => PaymentIntentStatus::Failed, 'failure_reason' => $reason]);
            }

            $event->update(['status' => 'processed', 'processed_at' => now()]);

            return $event->fresh();
        });
    }

    public function amountToMinor(string $amount): int
    {
        $amount = trim($amount);

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new InvalidArgumentException('Amount must contain no more than two decimal places.');
        }

        $whole = (int) $matches[1];
        $fraction = str_pad($matches[2] ?? '', 2, '0');
        $minor = ($whole * 100) + (int) $fraction;

        if ($minor < 1) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return $minor;
    }

    private function settleLocked(Deposit $deposit, string $externalReference, ?int $actorId = null): Deposit
    {
        if ($deposit->status === DepositStatus::Completed) {
            return $deposit->fresh(['paymentIntent']);
        }

        if ($deposit->status !== DepositStatus::Pending || trim($externalReference) === '') {
            throw new LogicException('Only pending deposits with an external reference can be settled.');
        }

        $wallet = $this->walletService->wallet($deposit->user, WalletType::Available, $deposit->currency);
        $cashAccount = $this->ledgerService->systemAccount(
            "cash:{$deposit->currency}",
            "Cash clearing {$deposit->currency}",
            $deposit->currency,
            LedgerAccountType::Asset,
        );
        $this->ledgerService->post([
            'type' => LedgerEntryType::Deposit,
            'currency' => $deposit->currency,
            'description' => "Deposit {$deposit->reference} settled",
            'idempotency_key' => "deposit-settlement:{$deposit->getKey()}",
            'actor_id' => $actorId,
            'metadata' => ['deposit_id' => $deposit->getKey(), 'external_reference' => $externalReference],
            'lines' => [
                ['account_id' => $cashAccount->getKey(), 'debit_minor' => $deposit->amount_minor],
                ['account_id' => $wallet->ledger_account_id, 'credit_minor' => $deposit->amount_minor],
            ],
        ]);
        $deposit->update(['status' => DepositStatus::Completed, 'external_reference' => $externalReference, 'settled_at' => now()]);
        $deposit->paymentIntent?->update(['status' => PaymentIntentStatus::Succeeded, 'succeeded_at' => now()]);

        return $deposit->fresh(['paymentIntent']);
    }
}
