<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupportReplyRequest;
use App\Http\Requests\Admin\SupportTicketUpdateRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use App\Services\SupportTicketService;
use App\SupportTicketCategory;
use App\SupportTicketPriority;
use App\SupportTicketStatus;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Storage;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $category = $request->string('category')->toString();
        $search = $request->string('search')->trim()->toString();
        $query = SupportTicket::query()->with(['user', 'assignee'])->latest('last_reply_at')->latest();

        if (in_array($status, array_map(fn (SupportTicketStatus $item): string => $item->value, SupportTicketStatus::cases()), true)) {
            $query->where('status', $status);
        } else {
            $status = '';
        }
        if (in_array($priority, array_map(fn (SupportTicketPriority $item): string => $item->value, SupportTicketPriority::cases()), true)) {
            $query->where('priority', $priority);
        } else {
            $priority = '';
        }
        if (in_array($category, array_map(fn (SupportTicketCategory $item): string => $item->value, SupportTicketCategory::cases()), true)) {
            $query->where('category', $category);
        } else {
            $category = '';
        }
        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search): void {
                $like = "%{$search}%";
                $searchQuery->where('ticket_number', 'like', $like)->orWhere('subject', 'like', $like);
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        return view('admin.support.index', [
            'tickets' => $tickets,
            'status' => $status,
            'priority' => $priority,
            'category' => $category,
            'search' => $search,
            'statuses' => SupportTicketStatus::cases(),
            'priorities' => SupportTicketPriority::cases(),
            'categories' => SupportTicketCategory::cases(),
        ]);
    }

    public function show(SupportTicket $supportTicket): View
    {
        $supportTicket->load(['user', 'assignee', 'messages.author', 'messages.attachments']);

        return view('admin.support.show', [
            'ticket' => $supportTicket,
            'staff' => User::query()->whereIn('role', array_map(fn (UserRole $role): string => $role->value, [UserRole::Admin, UserRole::Finance, UserRole::Compliance, UserRole::Support]))->orderBy('name')->get(),
            'statuses' => SupportTicketStatus::cases(),
            'priorities' => SupportTicketPriority::cases(),
            'categories' => SupportTicketCategory::cases(),
        ]);
    }

    public function update(SupportTicketUpdateRequest $request, SupportTicket $supportTicket, SupportTicketService $supportTicketService): RedirectResponse
    {
        $validated = $request->validated();
        $validated['assigned_to_id'] = isset($validated['assigned_to_id']) && $validated['assigned_to_id'] !== ''
            ? (int) $validated['assigned_to_id']
            : null;

        if ($validated['assigned_to_id'] !== null) {
            $assignee = User::query()->findOrFail($validated['assigned_to_id']);
            abort_unless($assignee->canAccessAdminArea(), 422, 'The selected assignee is not support staff.');
        }

        $supportTicketService->updateDetails($supportTicket, $validated);

        return back()->with('status', 'Ticket details updated.');
    }

    public function reply(SupportReplyRequest $request, SupportTicket $supportTicket, SupportTicketService $supportTicketService): RedirectResponse
    {
        $supportTicketService->reply(
            $supportTicket,
            $request->user(),
            $request->validated('body'),
            $request->boolean('is_internal'),
            $request->file('attachment'),
        );

        return back()->with('status', $request->boolean('is_internal') ? 'Internal note added.' : 'Reply sent to the customer.');
    }

    public function downloadAttachment(SupportTicket $supportTicket, SupportTicketAttachment $attachment): StreamedResponse
    {
        $attachment->load('message');
        abort_unless($attachment->message?->support_ticket_id === $supportTicket->getKey(), 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
