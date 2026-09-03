<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationLine extends Model
{
    use HasFactory;

    protected $fillable = ['language_id', 'group_name', 'key', 'value'];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
