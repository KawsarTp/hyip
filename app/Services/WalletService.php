<?php

namespace App\Services;

use App\LedgerAccountType;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Models\Wallet;
use App\WalletType;
use Illuminate\Database\Eloquent\Collection;

class WalletService
{
    /**
     * Provision every wallet bucket for a user's currency.
     *
     * @return Collection<int, Wallet>
     */
    public function walletsFor(User $user, string $currency = 'USD'): Collection
    {
        return new Collection(array_map(
            fn (WalletType $type): Wallet => $this->wallet($user, $type, $currency),
            WalletType::cases(),
        ));
    }

    public function wallet(User $user, WalletType $type, string $currency = 'USD'): Wallet
    {
        $currency = strtoupper($currency);

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Currency must be a three-letter ISO code.');
        }

        $account = LedgerAccount::query()->firstOrCreate(
            ['code' => "wallet:{$user->getKey()}:{$currency}:{$type->value}"],
            [
                'user_id' => $user->getKey(),
                'name' => "{$type->name} wallet",
                'currency' => $currency,
                'type' => LedgerAccountType::Liability,
            ],
        );

        return Wallet::query()->firstOrCreate(
            [
                'user_id' => $user->getKey(),
                'currency' => $currency,
                'type' => $type,
            ],
            ['ledger_account_id' => $account->getKey(), 'balance_minor' => 0],
        );
    }
}
