<?php

namespace App\Models;

use App\PlanStatus;
use Database\Factories\InvestmentPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentPlan extends Model
{
    /** @use HasFactory<InvestmentPlanFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'currency', 'risk_rating', 'minimum_amount_minor', 'maximum_amount_minor', 'capacity_minor', 'current_invested_minor', 'term_days', 'lock_days', 'projected_rate_bps', 'rate_type', 'calculation_frequency', 'day_count_basis', 'compounding', 'status', 'version', 'effective_at', 'sort_order'];

    protected function casts(): array
    {
        return [
            'status' => PlanStatus::class,
            'minimum_amount_minor' => 'integer',
            'maximum_amount_minor' => 'integer',
            'capacity_minor' => 'integer',
            'current_invested_minor' => 'integer',
            'term_days' => 'integer',
            'lock_days' => 'integer',
            'projected_rate_bps' => 'integer',
            'day_count_basis' => 'integer',
            'compounding' => 'boolean',
            'version' => 'integer',
            'effective_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }
}
