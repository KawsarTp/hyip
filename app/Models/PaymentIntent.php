<?php

namespace App\Models;

use App\PaymentIntentStatus;
use Database\Factories\PaymentIntentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentIntent extends Model
{
    /** @use HasFactory<PaymentIntentFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'payment_gateway_id', 'reference', 'provider', 'provider_intent_id', 'method', 'currency', 'amount_minor', 'status', 'idempotency_key', 'metadata', 'expires_at', 'succeeded_at', 'failure_reason'];

    protected function casts(): array
    {
        return [
            'status' => PaymentIntentStatus::class,
            'payment_gateway_id' => 'integer',
            'amount_minor' => 'integer',
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'succeeded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function deposit(): HasOne
    {
        return $this->hasOne(Deposit::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DepositEvent::class);
    }
}
