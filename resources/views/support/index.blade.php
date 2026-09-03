@component('layouts.shell', ['active' => 'support', 'title' => 'Help & support'])
    <div class="mx-auto max-w-[1100px]">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-[#7b879a]">Open a secure conversation with the Arcadia support team.</p>
                <h2 class="display-copy mt-2 text-3xl font-semibold sm:text-4xl">Your support desk.</h2>
            </div>
            <a href="{{ route('support.tickets.create') }}" class="inline-flex w-fit items-center gap-2 rounded-xl bg-[#0a1324] px-4 py-3 text-xs font-semibold text-white"><x-icon name="plus" size="15" class="text-[#b9f7d2]" /> New ticket</a>
        </div>

        @if (session('status'))
            <div class="mt-7 rounded-2xl border border-[#cdebd8] bg-[#eaf9f0] px-4 py-3 text-xs font-medium text-[#2f8d63]">{{ session('status') }}</div>
        @endif

        <section class="app-surface mt-8 overflow-hidden rounded-[24px] border border-[#e2e7ef] bg-white">
            <div class="border-b border-[#edf0f4] px-6 py-5 sm:px-7">
                <h3 class="text-sm font-semibold">Your conversations</h3>
                <p class="mt-1 text-[11px] text-[#9aa5b7]">Keep financial and account questions in one auditable thread.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-[.14em] text-[#a3adbd]">
                            <th class="px-6 py-4 font-semibold sm:px-7">Ticket</th>
                            <th class="px-4 py-4 font-semibold">Category</th>
                            <th class="px-4 py-4 font-semibold">Priority</th>
                            <th class="px-4 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 text-right font-semibold sm:px-7">Last activity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f2f6]">
                        @forelse ($tickets as $ticket)
                            <tr class="transition hover:bg-[#fbfcfe]">
                                <td class="px-6 py-4 sm:px-7">
                                    <a href="{{ route('support.tickets.show', $ticket) }}" class="text-xs font-semibold text-[#7668f6]">{{ $ticket->ticket_number }}</a>
                                    <p class="mt-1 max-w-[320px] truncate text-xs text-[#536078]">{{ $ticket->subject }}</p>
                                </td>
                                <td class="px-4 py-4 text-[11px] capitalize text-[#7b879a]">{{ str($ticket->category->value)->replace('_', ' ')->title() }}</td>
                                <td class="px-4 py-4"><span class="rounded-full {{ in_array($ticket->priority->value, ['urgent', 'high'], true) ? 'bg-[#fff0ec] text-[#b94f3a]' : 'bg-[#f0efff] text-[#7668f6]' }} px-2.5 py-1 text-[10px] font-semibold">{{ str($ticket->priority->value)->title() }}</span></td>
                                <td class="px-4 py-4"><span class="rounded-full {{ in_array($ticket->status->value, ['closed', 'resolved'], true) ? 'bg-[#eaf9f0] text-[#36a675]' : 'bg-[#fff7db] text-[#bd8b1b]' }} px-2.5 py-1 text-[10px] font-semibold">{{ str($ticket->status->value)->replace('_', ' ')->title() }}</span></td>
                                <td class="px-6 py-4 text-right text-[11px] text-[#7b879a] sm:px-7">{{ $ticket->last_reply_at?->diffForHumans() ?? $ticket->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-16 text-center text-xs text-[#9aa5b7]">No support tickets yet. We are ready when you are.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tickets->hasPages())
                <div class="border-t border-[#edf0f4] px-6 py-4 sm:px-7">{{ $tickets->links() }}</div>
            @endif
        </section>
    </div>
@endcomponent
