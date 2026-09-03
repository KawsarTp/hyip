<?php

namespace App\Models;

use App\SupportTicketCategory;
use App\SupportTicketPriority;
use App\SupportTicketStatus;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_to_id',
        'ticket_number',
        'subject',
        'category',
        'priority',
        'status',
        'description',
        'sla_due_at',
        'first_response_at',
        'last_reply_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => SupportTicketCategory::class,
            'priority' => SupportTicketPriority::class,
            'status' => SupportTicketStatus::class,
            'sla_due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'last_reply_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }
}
