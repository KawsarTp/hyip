<?php

namespace App\Models;

use App\ReferralCommissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCommission extends Model
{
    protected $fillable = ['referrer_id', 'referred_user_id', 'investment_id', 'reference', 'event_key', 'currency', 'qualifying_amount_minor', 'commission_amount_minor', 'commission_rate_bps', 'trigger', 'status', 'hold_until', 'approved_by', 'ledger_entry_id', 'approved_at', 'released_at', 'clawed_back_at', 'review_notes', 'metadata'];

    protected function casts(): array
    {
        return [
            'status' => ReferralCommissionStatus::class,
            'qualifying_amount_minor' => 'integer',
            'commission_amount_minor' => 'integer',
            'commission_rate_bps' => 'integer',
            'ledger_entry_id' => 'integer',
            'hold_until' => 'datetime',
            'approved_at' => 'datetime',
            'released_at' => 'datetime',
            'clawed_back_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
