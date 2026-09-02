<?php

namespace App\Models;

use Database\Factories\WithdrawalMethodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WithdrawalMethod extends Model
{
    /** @use HasFactory<WithdrawalMethodFactory> */
    use HasFactory;

    protected $fillable = ['name', 'method', 'currency', 'instructions', 'destination_label', 'minimum_amount_minor', 'maximum_amount_minor', 'fee_fixed_minor', 'fee_percent_bps', 'is_enabled', 'sort_order'];

    protected function casts(): array
    {
        return [
            'minimum_amount_minor' => 'integer',
            'maximum_amount_minor' => 'integer',
            'fee_fixed_minor' => 'integer',
            'fee_percent_bps' => 'integer',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }
}
