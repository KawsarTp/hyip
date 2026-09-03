<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = ['key', 'name', 'subject', 'body_html', 'body_text', 'variables', 'locale', 'is_enabled', 'version', 'updated_by'];

    protected function casts(): array
    {
        return ['variables' => 'array', 'is_enabled' => 'boolean', 'version' => 'integer'];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
