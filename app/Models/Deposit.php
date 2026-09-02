<?php

namespace App\Models;

use App\DepositStatus;
use Database\Factories\DepositFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deposit extends Model
{
    /** @use HasFactory<DepositFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'payment_intent_id', 'reference', 'idempotency_key', 'method', 'currency', 'amount_minor', 'status', 'external_reference', 'settled_at', 'rejection_reason', 'metadata'];

    protected function casts(): array
    {
        return [
            'status' => DepositStatus::class,
            'payment_gateway_id' => 'integer',
            'amount_minor' => 'integer',
            'metadata' => 'array',
            'settled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DepositEvent::class);
    }
}
