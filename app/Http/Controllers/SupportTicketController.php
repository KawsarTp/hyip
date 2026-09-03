<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupportReplyRequest;
use App\Http\Requests\SupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use App\Services\SupportTicketService;
use App\SupportTicketCategory;
use App\SupportTicketPriority;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use LogicException;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $tickets = $user->supportTickets()
            ->with('assignee')
            ->latest('last_reply_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('support.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('support.create', [
            'categories' => SupportTicketCategory::cases(),
            'priorities' => SupportTicketPriority::cases(),
        ]);
    }

    public function store(SupportTicketRequest $request, SupportTicketService $supportTicketService): RedirectResponse
    {
        $ticket = $supportTicketService->create(
            $request->user(),
            $request->validated(),
            $request->file('attachment'),
        );

        return redirect()->route('support.tickets.show', $ticket)->with('status', 'Your support ticket has been submitted.');
    }

    public function show(Request $request, SupportTicket $supportTicket): View
    {
        $this->authorizeOwner($request, $supportTicket);
        $supportTicket->load([
            'assignee',
            'messages' => fn ($query) => $query->where('is_internal', false)->with(['author', 'attachments'])->oldest(),
        ]);

        return view('support.show', ['ticket' => $supportTicket]);
    }

    public function reply(SupportReplyRequest $request, SupportTicket $supportTicket, SupportTicketService $supportTicketService): RedirectResponse
    {
        $this->authorizeOwner($request, $supportTicket);

        try {
            $supportTicketService->reply(
                $supportTicket,
                $request->user(),
                $request->validated('body'),
                false,
                $request->file('attachment'),
            );
        } catch (LogicException $exception) {
            return back()->withInput()->withErrors(['ticket' => $exception->getMessage()]);
        }

        return back()->with('status', 'Your reply has been added to the ticket.');
    }

    public function close(Request $request, SupportTicket $supportTicket, SupportTicketService $supportTicketService): RedirectResponse
    {
        $this->authorizeOwner($request, $supportTicket);

        try {
            $supportTicketService->closeByUser($supportTicket, $request->user());
        } catch (LogicException $exception) {
            return back()->withErrors(['ticket' => $exception->getMessage()]);
        }

        return back()->with('status', 'This ticket has been closed.');
    }

    public function reopen(Request $request, SupportTicket $supportTicket, SupportTicketService $supportTicketService): RedirectResponse
    {
        $this->authorizeOwner($request, $supportTicket);

        try {
            $supportTicketService->reopen($supportTicket, $request->user());
        } catch (LogicException $exception) {
            return back()->withErrors(['ticket' => $exception->getMessage()]);
        }

        return back()->with('status', 'This ticket has been reopened.');
    }

    public function downloadAttachment(Request $request, SupportTicket $supportTicket, SupportTicketAttachment $attachment): StreamedResponse
    {
        $this->authorizeOwner($request, $supportTicket);
        $attachment->load('message');
        abort_unless($attachment->message?->support_ticket_id === $supportTicket->getKey(), 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function authorizeOwner(Request $request, SupportTicket $ticket): void
    {
        abort_unless($ticket->user_id === $request->user()->getKey(), 403);
    }
}
