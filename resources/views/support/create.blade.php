@component('layouts.shell', ['active' => 'support', 'title' => 'New support ticket'])
    <div class="mx-auto max-w-[1000px]">
        <a href="{{ route('support.tickets.index') }}" class="text-xs font-semibold text-[#7668f6]">← Back to support</a>
        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_300px]">
            <section class="app-surface rounded-[24px] border border-[#e2e7ef] bg-white p-6 sm:p-8">
                <p class="text-[10px] font-semibold uppercase tracking-[.16em] text-[#9aa5b7]">Secure conversation</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-[-.04em]">How can we help?</h2>
                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-[#ffd8cf] bg-[#fff0ec] px-4 py-3 text-xs text-[#b94f3a]"><ul class="grid gap-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <form action="{{ route('support.tickets.store') }}" method="post" enctype="multipart/form-data" class="mt-7 grid gap-5">
                    @csrf
                    <div><label for="subject" class="text-[11px] font-semibold text-[#536078]">Subject</label><input id="subject" name="subject" value="{{ old('subject') }}" required maxlength="200" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 text-sm outline-none focus:border-[#7668f6]" placeholder="What do you need help with?"></div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label for="category" class="text-[11px] font-semibold text-[#536078]">Category</label><select id="category" name="category" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 text-sm outline-none focus:border-[#7668f6]"><option value="">Choose a category</option>@foreach ($categories as $category)<option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ str($category->value)->replace('_', ' ')->title() }}</option>@endforeach</select></div>
                        <div><label for="priority" class="text-[11px] font-semibold text-[#536078]">Priority</label><select id="priority" name="priority" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 text-sm outline-none focus:border-[#7668f6]"><option value="">Choose priority</option>@foreach ($priorities as $priority)<option value="{{ $priority->value }}" @selected(old('priority', 'normal') === $priority->value)>{{ str($priority->value)->title() }}</option>@endforeach</select></div>
                    </div>
                    <div><label for="description" class="text-[11px] font-semibold text-[#536078]">Message</label><textarea id="description" name="description" rows="9" required minlength="10" maxlength="20000" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-4 py-3 text-sm leading-6 outline-none focus:border-[#7668f6]" placeholder="Share the details, reference numbers, and what outcome you need.">{{ old('description') }}</textarea></div>
                    <div><label for="attachment" class="text-[11px] font-semibold text-[#536078]">Attachment <span class="font-normal text-[#9aa5b7]">(optional, max 10 MB)</span></label><input id="attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.doc,.docx" class="mt-2 block w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 text-xs text-[#7b879a]"><p class="mt-2 text-[10px] text-[#9aa5b7]">Never upload passwords, seed phrases, or payment credentials.</p></div>
                    <button class="rounded-xl bg-[#0a1324] px-4 py-3.5 text-xs font-semibold text-white">Submit ticket <x-icon name="arrow-right" size="14" class="ml-1 inline text-[#b9f7d2]" /></button>
                </form>
            </section>
            <aside class="rounded-[24px] bg-[#0a1324] p-6 text-white sm:p-8"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#b9f7d2] text-[#0a1324]"><x-icon name="help" size="18" /></span><h3 class="mt-6 text-lg font-semibold">A clear path to resolution.</h3><div class="mt-5 grid gap-4 text-xs leading-5 text-white/55"><p><span class="font-semibold text-white">1.</span> Include the relevant reference or ticket number.</p><p><span class="font-semibold text-white">2.</span> A support specialist will review and reply in this thread.</p><p><span class="font-semibold text-white">3.</span> Your attachments stay private and access-controlled.</p></div><div class="mt-8 border-t border-white/10 pt-5 text-[11px] text-white/40">Urgent payout or account-hold questions should include the exact reference shown in your account.</div></aside>
        </div>
    </div>
@endcomponent
