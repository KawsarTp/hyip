<?php

namespace App\Models;

use App\KycStatus;
use Database\Factories\KycProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KycProfile extends Model
{
    /** @use HasFactory<KycProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'legal_name',
        'date_of_birth',
        'nationality',
        'country',
        'tax_residency',
        'address_line1',
        'address_line2',
        'city',
        'region',
        'postal_code',
        'review_notes',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => KycStatus::class,
            'date_of_birth' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(KycReview::class);
    }
}
