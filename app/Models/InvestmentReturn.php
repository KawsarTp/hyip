<?php

namespace App\Models;

use App\InvestmentReturnStatus;
use Database\Factories\InvestmentReturnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentReturn extends Model
{
    /** @use HasFactory<InvestmentReturnFactory> */
    use HasFactory;

    protected $fillable = ['investment_id', 'period_key', 'period_start', 'period_end', 'opening_principal_minor', 'eligible_days', 'rate_bps', 'return_amount_minor', 'status', 'formula_version', 'calculation_snapshot', 'approved_by', 'ledger_entry_id', 'approved_at', 'released_at'];

    protected function casts(): array
    {
        return [
            'status' => InvestmentReturnStatus::class,
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'opening_principal_minor' => 'integer',
            'eligible_days' => 'integer',
            'rate_bps' => 'integer',
            'return_amount_minor' => 'integer',
            'calculation_snapshot' => 'array',
            'approved_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }
}
