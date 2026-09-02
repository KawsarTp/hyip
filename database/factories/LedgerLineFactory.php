<?php

namespace Database\Factories;

use App\Models\LedgerLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerLine>
 */
class LedgerLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ledger_entry_id' => LedgerEntryFactory::new(),
            'ledger_account_id' => LedgerAccountFactory::new(),
            'debit_minor' => 0,
            'credit_minor' => 100,
            'description' => null,
        ];
    }
}
