<article class="rounded-2xl border {{ $message->is_internal ? 'border-[#f5d7a1] bg-[#fffaf0]' : ($message->author?->getKey() === $ticket->user_id ? 'border-[#e2e7ef] bg-white' : 'border-[#d8d2ff] bg-[#f8f7ff]') }} p-5">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold text-[#0a1324]">{{ $message->is_internal ? 'Internal note' : ($message->author?->name ?? 'Support team') }}</p>
            <p class="mt-1 text-[10px] text-[#9aa5b7]">{{ $message->created_at?->format('M d, Y · H:i') }}</p>
        </div>
        @if ($message->is_internal)
            <span class="rounded-full bg-[#fff0b8] px-2.5 py-1 text-[10px] font-semibold text-[#806e22]">Staff only</span>
        @endif
    </div>
    <div class="prose prose-sm mt-4 max-w-none whitespace-pre-line text-xs leading-6 text-[#536078]">{{ $message->body }}</div>
    @if ($message->attachments->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-2 border-t border-black/5 pt-4">
            @foreach ($message->attachments as $attachment)
                <a href="{{ $admin ? route('admin.support.attachments.download', [$ticket, $attachment]) : route('support.tickets.attachments.download', [$ticket, $attachment]) }}" class="inline-flex items-center gap-2 rounded-lg border border-[#dfe5ec] bg-white px-3 py-2 text-[10px] font-semibold text-[#536078] hover:border-[#7668f6]">
                    <x-icon name="file" size="13" />
                    {{ str($attachment->original_name)->limit(28) }}
                </a>
            @endforeach
        </div>
    @endif
</article>
