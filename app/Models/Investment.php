<?php

namespace App\Models;

use App\InvestmentStatus;
use Database\Factories\InvestmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investment extends Model
{
    /** @use HasFactory<InvestmentFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'investment_plan_id', 'reference', 'plan_version', 'currency', 'principal_minor', 'projected_return_minor', 'accrued_return_minor', 'released_return_minor', 'status', 'start_at', 'maturity_at', 'last_calculated_at', 'matured_at', 'completed_at', 'risk_acknowledged_at', 'terms_snapshot', 'metadata'];

    protected function casts(): array
    {
        return [
            'status' => InvestmentStatus::class,
            'plan_version' => 'integer',
            'principal_minor' => 'integer',
            'projected_return_minor' => 'integer',
            'accrued_return_minor' => 'integer',
            'released_return_minor' => 'integer',
            'start_at' => 'datetime',
            'maturity_at' => 'datetime',
            'last_calculated_at' => 'datetime',
            'matured_at' => 'datetime',
            'completed_at' => 'datetime',
            'risk_acknowledged_at' => 'datetime',
            'terms_snapshot' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function investmentPlan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(InvestmentReturn::class);
    }
}
