<?php

namespace App\Models;

use Database\Factories\FeeRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeRule extends Model
{
    /** @use HasFactory<FeeRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'event',
        'currency',
        'country',
        'fee_fixed_minor',
        'fee_percent_bps',
        'maximum_fee_minor',
        'tax_fixed_minor',
        'tax_percent_bps',
        'tax_basis',
        'priority',
        'is_enabled',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'fee_fixed_minor' => 'integer',
            'fee_percent_bps' => 'integer',
            'maximum_fee_minor' => 'integer',
            'tax_fixed_minor' => 'integer',
            'tax_percent_bps' => 'integer',
            'priority' => 'integer',
            'is_enabled' => 'boolean',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
        ];
    }
}
