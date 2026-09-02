<?php

namespace App\Models;

use Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory;

    protected $fillable = ['payment_gateway_id', 'label', 'bank_name', 'account_name', 'account_number', 'iban', 'swift_code', 'branch', 'instructions', 'currency', 'is_enabled', 'sort_order'];

    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'iban' => 'encrypted',
            'swift_code' => 'encrypted',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }
}
