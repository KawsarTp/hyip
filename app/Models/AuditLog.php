<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'action',
        'category',
        'auditable_type',
        'auditable_id',
        'route',
        'method',
        'request_id',
        'ip_address',
        'user_agent',
        'metadata',
        'before',
        'after',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
