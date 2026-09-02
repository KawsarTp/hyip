<?php

namespace App\Models;

use App\LedgerEntryStatus;
use App\LedgerEntryType;
use Database\Factories\LedgerEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory;

    protected $fillable = ['reference', 'type', 'status', 'currency', 'description', 'idempotency_key', 'actor_id', 'payload_hash', 'metadata', 'posted_at'];

    protected function casts(): array
    {
        return [
            'type' => LedgerEntryType::class,
            'status' => LedgerEntryStatus::class,
            'metadata' => 'array',
            'posted_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(LedgerLine::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
