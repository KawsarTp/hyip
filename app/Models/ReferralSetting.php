<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    protected $fillable = ['investment_commission_bps', 'hold_days', 'minimum_qualifying_investment_minor', 'is_enabled'];

    protected function casts(): array
    {
        return [
            'investment_commission_bps' => 'integer',
            'hold_days' => 'integer',
            'minimum_qualifying_investment_minor' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }
}
