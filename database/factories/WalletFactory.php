<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\WalletType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'ledger_account_id' => LedgerAccountFactory::new(),
            'currency' => 'USD',
            'type' => WalletType::Available,
            'balance_minor' => 0,
        ];
    }
}
