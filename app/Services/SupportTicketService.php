<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\SupportTicketPriority;
use App\SupportTicketStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class SupportTicketService
{
    public function __construct(private readonly EmailNotificationService $emailNotificationService) {}

    /**
     * @param  array{subject: string, category: string, priority: string, description: string}  $attributes
     */
    public function create(User $user, array $attributes, ?UploadedFile $attachment = null): SupportTicket
    {
        $storedPath = $attachment?->store('support-attachments', 'local');

        try {
            return DB::transaction(function () use ($user, $attributes, $attachment, $storedPath): SupportTicket {
                $ticket = SupportTicket::query()->create([
                    'user_id' => $user->getKey(),
                    'ticket_number' => $this->ticketNumber(),
                    'subject' => trim($attributes['subject']),
                    'category' => $attributes['category'],
                    'priority' => $attributes['priority'],
                    'status' => SupportTicketStatus::Open,
                    'description' => trim($attributes['description']),
                    'sla_due_at' => now()->addHours($this->slaHours(SupportTicketPriority::from($attributes['priority']))),
                    'last_reply_at' => now(),
                ]);
                $message = $ticket->messages()->create([
                    'user_id' => $user->getKey(),
                    'body' => trim($attributes['description']),
                    'is_internal' => false,
                ]);
                $this->storeAttachment($message, $user, $attachment, $storedPath);
                $this->queueCreatedNotifications($ticket, $user);

                return $ticket->fresh(['user', 'assignee']);
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function reply(SupportTicket $ticket, User $actor, string $body, bool $isInternal, ?UploadedFile $attachment = null): SupportTicket
    {
        $storedPath = $attachment?->store('support-attachments', 'local');
        $replyEventKey = (string) Str::uuid();

        try {
            return DB::transaction(function () use ($ticket, $actor, $body, $isInternal, $attachment, $storedPath, $replyEventKey): SupportTicket {
                $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($ticket->getKey());
                $message = $ticket->messages()->create([
                    'user_id' => $actor->getKey(),
                    'body' => trim($body),
                    'is_internal' => $isInternal,
                ]);
                $this->storeAttachment($message, $actor, $attachment, $storedPath);

                $status = $ticket->status;
                if (! $isInternal) {
                    $status = $actor->getKey() === $ticket->user_id
                        ? SupportTicketStatus::WaitingForStaff
                        : SupportTicketStatus::WaitingForUser;
                }

                $ticket->update([
                    'status' => $status,
                    'last_reply_at' => now(),
                    'first_response_at' => ! $isInternal && $actor->getKey() !== $ticket->user_id && $ticket->first_response_at === null
                        ? now()
                        : $ticket->first_response_at,
                    'resolved_at' => $isInternal ? $ticket->resolved_at : null,
                    'closed_at' => $isInternal ? $ticket->closed_at : null,
                ]);

                if (! $isInternal) {
                    $this->queueReplyNotification($ticket, $actor, $body, $replyEventKey);
                }

                return $ticket->fresh(['user', 'assignee']);
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    /**
     * @param  array{status: string, priority: string, category: string, assigned_to_id: int|null}  $attributes
     */
    public function updateDetails(SupportTicket $ticket, array $attributes): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $attributes): SupportTicket {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($ticket->getKey());
            $status = SupportTicketStatus::from($attributes['status']);
            $wasChanged = $ticket->status !== $status || $ticket->priority->value !== $attributes['priority'] || $ticket->category->value !== $attributes['category'] || $ticket->assigned_to_id !== $attributes['assigned_to_id'];
            $ticket->update([
                'status' => $status,
                'priority' => $attributes['priority'],
                'category' => $attributes['category'],
                'assigned_to_id' => $attributes['assigned_to_id'],
                'resolved_at' => $status === SupportTicketStatus::Resolved ? ($ticket->resolved_at ?? now()) : null,
                'closed_at' => $status === SupportTicketStatus::Closed ? ($ticket->closed_at ?? now()) : null,
            ]);

            if ($wasChanged) {
                $ticket->loadMissing(['user', 'assignee']);
                $this->emailNotificationService->queueForUser(
                    $ticket->user,
                    'ticket_status_updated',
                    $this->ticketVariables($ticket) + ['status' => $status->value, 'assigned_to' => $ticket->assignee?->name],
                    "support-ticket:{$ticket->getKey()}:status:{$ticket->updated_at?->timestamp}:".Str::uuid(),
                );
                if ($ticket->assignee !== null) {
                    $this->emailNotificationService->queueForUser(
                        $ticket->assignee,
                        'ticket_status_updated',
                        $this->ticketVariables($ticket) + ['status' => $status->value, 'assigned_to' => $ticket->assignee->name],
                        "support-ticket:{$ticket->getKey()}:assigned:{$ticket->updated_at?->timestamp}:".Str::uuid(),
                    );
                }
            }

            return $ticket->fresh(['user', 'assignee']);
        });
    }

    public function closeByUser(SupportTicket $ticket, User $user): SupportTicket
    {
        return $this->changeStatus($ticket, $user, SupportTicketStatus::Closed, true);
    }

    public function reopen(SupportTicket $ticket, User $actor): SupportTicket
    {
        $status = $actor->getKey() === $ticket->user_id ? SupportTicketStatus::WaitingForStaff : SupportTicketStatus::Open;

        return $this->changeStatus($ticket, $actor, $status, true);
    }

    private function changeStatus(SupportTicket $ticket, User $actor, SupportTicketStatus $status, bool $notifySupport): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $actor, $status, $notifySupport): SupportTicket {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($ticket->getKey());
            if ($actor->getKey() !== $ticket->user_id && ! $actor->canAccessAdminArea()) {
                throw new LogicException('You are not allowed to update this ticket.');
            }

            $ticket->update([
                'status' => $status,
                'resolved_at' => $status === SupportTicketStatus::Resolved ? ($ticket->resolved_at ?? now()) : null,
                'closed_at' => $status === SupportTicketStatus::Closed ? now() : null,
            ]);
            $ticket->loadMissing(['user', 'assignee']);

            if ($notifySupport) {
                $this->emailNotificationService->queueToSupportTeam(
                    'ticket_status_updated',
                    $this->ticketVariables($ticket) + ['status' => $status->value, 'updated_by' => $actor->name],
                    "support-ticket:{$ticket->getKey()}:status:{$status->value}:".Str::uuid(),
                );
            }

            return $ticket->fresh(['user', 'assignee']);
        });
    }

    private function queueCreatedNotifications(SupportTicket $ticket, User $user): void
    {
        $variables = $this->ticketVariables($ticket) + ['user_name' => $user->name];
        $this->emailNotificationService->queueForUser(
            $user,
            'ticket_created',
            $variables,
            "support-ticket:{$ticket->getKey()}:created:user",
        );
        $this->emailNotificationService->queueToSupportTeam(
            'ticket_created',
            $variables,
            "support-ticket:{$ticket->getKey()}:created:support",
        );
    }

    private function queueReplyNotification(SupportTicket $ticket, User $actor, string $body, string $replyEventKey): void
    {
        $variables = $this->ticketVariables($ticket) + [
            'user_name' => $ticket->user->name,
            'reply_preview' => Str::limit(trim($body), 180),
            'replied_by' => $actor->name,
        ];
        if ($actor->getKey() === $ticket->user_id) {
            $this->emailNotificationService->queueToSupportTeam(
                'ticket_reply',
                $variables,
                "support-ticket:{$ticket->getKey()}:reply:{$replyEventKey}:support",
            );

            return;
        }

        $this->emailNotificationService->queueForUser(
            $ticket->user,
            'ticket_reply',
            $variables,
            "support-ticket:{$ticket->getKey()}:reply:{$replyEventKey}:user",
        );
    }

    /**
     * @return array<string, scalar|null>
     */
    private function ticketVariables(SupportTicket $ticket): array
    {
        return [
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'category' => $ticket->category->value,
            'priority' => $ticket->priority->value,
            'status' => $ticket->status->value,
            'ticket_url' => route('support.tickets.show', $ticket),
        ];
    }

    private function ticketNumber(): string
    {
        do {
            $number = 'TKT-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (SupportTicket::query()->where('ticket_number', $number)->exists());

        return $number;
    }

    private function slaHours(SupportTicketPriority $priority): int
    {
        return match ($priority) {
            SupportTicketPriority::Low => 72,
            SupportTicketPriority::Normal => 48,
            SupportTicketPriority::High => 24,
            SupportTicketPriority::Urgent => 8,
        };
    }

    private function storeAttachment(SupportTicketMessage $message, User $user, ?UploadedFile $attachment, ?string $storedPath): void
    {
        if ($attachment === null || $storedPath === null) {
            return;
        }

        $message->attachments()->create([
            'uploaded_by_id' => $user->getKey(),
            'disk' => 'local',
            'path' => $storedPath,
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getMimeType(),
            'size' => $attachment->getSize() ?: 0,
        ]);
    }
}
