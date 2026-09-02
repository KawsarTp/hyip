<?php

namespace App\Models;

use App\KycStatus;
use Database\Factories\KycReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycReview extends Model
{
    /** @use HasFactory<KycReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'kyc_profile_id',
        'reviewer_id',
        'decision',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'decision' => KycStatus::class,
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(KycProfile::class, 'kyc_profile_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
