<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPage extends Model
{
    protected $fillable = ['slug', 'title', 'excerpt', 'body', 'status', 'meta_title', 'meta_description', 'published_at', 'updated_by'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
