<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'rate_to_default',
        'rate_source',
        'rate_updated_at',
        'symbol_position',
        'thousands_separator',
        'decimal_separator',
        'is_enabled',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'rate_to_default' => 'decimal:10',
            'rate_updated_at' => 'datetime',
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function label(): string
    {
        return $this->code.' · '.$this->name;
    }

    public function format(float|int|string $amount): string
    {
        $formatted = number_format((float) $amount, $this->decimal_places, $this->decimal_separator, $this->thousands_separator);

        return $this->symbol_position === 'suffix'
            ? $formatted.' '.$this->symbol
            : $this->symbol.$formatted;
    }
}
