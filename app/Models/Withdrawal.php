<?php

namespace App\Models;

use App\WithdrawalStatus;
use Database\Factories\WithdrawalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    /** @use HasFactory<WithdrawalFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'withdrawal_method_id', 'reference', 'idempotency_key', 'currency', 'gross_amount_minor', 'fee_amount_minor', 'net_amount_minor', 'status', 'destination_details', 'external_reference', 'rejection_reason', 'reviewed_by', 'reserved_at', 'reviewed_at', 'completed_at', 'metadata'];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatus::class,
            'gross_amount_minor' => 'integer',
            'fee_amount_minor' => 'integer',
            'net_amount_minor' => 'integer',
            'destination_details' => 'encrypted:array',
            'reserved_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawalMethod(): BelongsTo
    {
        return $this->belongsTo(WithdrawalMethod::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
