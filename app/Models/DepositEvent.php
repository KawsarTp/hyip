<?php

namespace App\Models;

use Database\Factories\DepositEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositEvent extends Model
{
    /** @use HasFactory<DepositEventFactory> */
    use HasFactory;

    protected $fillable = ['payment_intent_id', 'deposit_id', 'provider', 'provider_event_id', 'event_type', 'payload', 'status', 'processed_at', 'error_message'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime'];
    }

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }
}
