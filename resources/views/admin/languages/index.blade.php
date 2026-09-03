@component('layouts.shell', ['admin' => true, 'active' => 'languages', 'title' => 'Languages'])
    <div class="mx-auto max-w-[1180px]">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-[#7b879a]">Translate every customer-facing interface and manage regional language preferences.</p>
                <h2 class="display-copy mt-2 text-3xl font-semibold sm:text-4xl">Language center.</h2>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.currencies.index') }}" class="rounded-xl border border-[#dfe5ec] bg-white px-4 py-3 text-[11px] font-semibold text-[#536078]">Manage currencies →</a>
                <button type="button" data-open-modal="language-create" class="rounded-xl bg-[#0a1324] px-4 py-3 text-[11px] font-semibold text-white">+ Add language</button>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-7 rounded-2xl border border-[#cdebd8] bg-[#eaf9f0] px-4 py-3 text-xs font-medium text-[#2f8d63]">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-7 rounded-2xl border border-[#ffd8cf] bg-[#fff0ec] px-4 py-3 text-xs text-[#b94f3a]"><ul class="grid gap-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="app-surface mt-8 rounded-[24px] border border-[#e2e7ef] bg-white p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4"><div><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-[#9aa5b7]">Translation catalog</p><h3 class="mt-2 text-xl font-semibold">Installed languages</h3><p class="mt-2 text-xs leading-5 text-[#9aa5b7]">Each language can have its own translated interface, fallback behavior, and text direction.</p></div><span class="rounded-xl bg-[#f8f9fc] px-3 py-2 text-[11px] font-semibold text-[#536078]">{{ $languages->where('is_enabled', true)->count() }} active</span></div>
            <div class="mt-6 grid gap-3">
                @forelse ($languages as $language)
                    <div class="rounded-2xl border border-[#edf0f4] p-4">
                        <div class="flex flex-wrap items-start justify-between gap-4"><div class="flex min-w-0 items-center gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#eef3f8] text-[10px] font-bold uppercase text-[#536078]">{{ $language->code }}</span><div class="min-w-0"><p class="truncate text-xs font-semibold">{{ $language->native_name }} <span class="font-normal text-[#9aa5b7]">· {{ $language->name }}</span></p><p class="mt-1 text-[10px] text-[#9aa5b7]">{{ $language->translations_count }} translation keys · {{ strtoupper($language->direction) }}</p></div></div><div class="flex shrink-0 items-center gap-2"><span class="rounded-full {{ $language->is_enabled ? 'bg-[#eaf9f0] text-[#36a675]' : 'bg-[#f1f3f7] text-[#8b96a8]' }} px-2 py-1 text-[9px] font-semibold">{{ $language->is_enabled ? 'Active' : 'Disabled' }}</span>@if ($language->is_default)<span class="rounded-full bg-[#f0efff] px-2 py-1 text-[9px] font-semibold text-[#7668f6]">Default</span>@endif</div></div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('admin.languages.translations', $language) }}" class="rounded-lg bg-[#0a1324] px-3 py-2 text-[10px] font-semibold text-white">Edit translations</a>
                            <button type="button" data-open-modal="language-edit-{{ $language->id }}" class="rounded-lg border border-[#dfe5ec] px-3 py-2 text-[10px] font-semibold text-[#536078]">Edit settings</button>
                            @unless ($language->is_default)<form action="{{ route('admin.languages.default', $language) }}" method="post">@csrf @method('patch')<button class="rounded-lg border border-[#dfe5ec] px-3 py-2 text-[10px] font-semibold text-[#536078]">Make default</button></form>@endunless
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-[#dfe5ec] px-5 py-12 text-center text-xs text-[#9aa5b7]">No languages configured yet.</div>
                @endforelse
            </div>
        </section>
    </div>

    <div id="language-create" data-modal role="dialog" aria-modal="true" aria-labelledby="language-create-title" class="admin-modal">
        <div data-modal-backdrop class="admin-modal__backdrop"></div>
        <div class="admin-modal__card rounded-2xl border border-[#dee2e6] bg-white text-[#0a1324] shadow-[0_1rem_3rem_rgba(0,0,0,.2)]">
            <div class="flex items-start justify-between border-b border-[#dee2e6] px-5 py-4"><div><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-[#9aa5b7]">Translation catalog</p><h3 id="language-create-title" class="mt-1 text-lg font-semibold">Add language</h3></div><button type="button" data-close-modal class="grid h-8 w-8 place-items-center rounded-lg text-xl leading-none text-[#6c757d] transition hover:bg-[#f1f3f5] hover:text-[#212529]" aria-label="Close">×</button></div>
        <form action="{{ route('admin.languages.store') }}" method="post" class="grid gap-4 p-6">@csrf
            <div><label for="language_code" class="text-[11px] font-semibold text-[#536078]">Locale code</label><input id="language_code" name="code" required maxlength="10" placeholder="bn or ar-SA" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm lowercase outline-none focus:border-[#7668f6]"></div>
            <div class="grid gap-4 sm:grid-cols-2"><div><label for="language_name" class="text-[11px] font-semibold text-[#536078]">English name</label><input id="language_name" name="name" required placeholder="Bengali" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm outline-none focus:border-[#7668f6]"></div><div><label for="language_native_name" class="text-[11px] font-semibold text-[#536078]">Native name</label><input id="language_native_name" name="native_name" required placeholder="বাংলা" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm outline-none focus:border-[#7668f6]"></div></div>
            <div><label for="language_direction" class="text-[11px] font-semibold text-[#536078]">Text direction</label><select id="language_direction" name="direction" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm outline-none focus:border-[#7668f6]"><option value="ltr">Left to right (LTR)</option><option value="rtl">Right to left (RTL)</option></select></div>
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-[#dee2e6] bg-[#f8f9fa] px-6 py-4"><label class="flex items-center gap-2 text-[11px] font-semibold text-[#536078]"><input type="checkbox" name="is_enabled" value="1" checked class="rounded border-[#cfd7e4] text-[#7668f6]"> Available to users</label><input type="hidden" name="sort_order" value="0"><div class="flex gap-2"><button type="button" data-close-modal class="rounded-lg border border-[#ced4da] bg-white px-4 py-2 text-[11px] font-semibold text-[#495057] transition hover:bg-[#e9ecef]">Cancel</button><button class="rounded-lg bg-[#0a1324] px-4 py-2 text-[11px] font-semibold text-white transition hover:bg-[#17243b]">Save language</button></div></div>
        </form>
        </div>
    </div>

    @foreach ($languages as $language)
        <div id="language-edit-{{ $language->id }}" data-modal role="dialog" aria-modal="true" aria-labelledby="language-edit-title-{{ $language->id }}" class="admin-modal">
            <div data-modal-backdrop class="admin-modal__backdrop"></div>
            <div class="admin-modal__card rounded-2xl border border-[#dee2e6] bg-white text-[#0a1324] shadow-[0_1rem_3rem_rgba(0,0,0,.2)]">
                <div class="flex items-start justify-between border-b border-[#dee2e6] px-5 py-4"><div><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-[#9aa5b7]">Language settings</p><h3 id="language-edit-title-{{ $language->id }}" class="mt-1 text-lg font-semibold">{{ $language->native_name }}</h3></div><button type="button" data-close-modal class="grid h-8 w-8 place-items-center rounded-lg text-xl leading-none text-[#6c757d] transition hover:bg-[#f1f3f5] hover:text-[#212529]" aria-label="Close">×</button></div>
            <form action="{{ route('admin.languages.update', $language) }}" method="post" class="grid gap-4 p-6">@csrf @method('patch')
                <div class="grid gap-4 sm:grid-cols-2"><div><label class="text-[11px] font-semibold text-[#536078]">Locale code</label><input name="code" value="{{ $language->code }}" required maxlength="10" class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm uppercase"></div><div><label class="text-[11px] font-semibold text-[#536078]">English name</label><input name="name" value="{{ $language->name }}" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"></div><div><label class="text-[11px] font-semibold text-[#536078]">Native name</label><input name="native_name" value="{{ $language->native_name }}" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"></div><div><label class="text-[11px] font-semibold text-[#536078]">Text direction</label><select name="direction" class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"><option value="ltr" @selected($language->direction === 'ltr')>Left to right (LTR)</option><option value="rtl" @selected($language->direction === 'rtl')>Right to left (RTL)</option></select></div></div>
                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-[#dee2e6] bg-[#f8f9fa] px-6 py-4"><label class="flex items-center gap-2 text-[11px] font-semibold text-[#536078]"><input type="checkbox" name="is_enabled" value="1" @checked($language->is_enabled) class="rounded border-[#cfd7e4] text-[#7668f6]"> Available to users</label><input type="hidden" name="sort_order" value="{{ $language->sort_order }}"><input type="hidden" name="is_default" value="{{ $language->is_default ? 1 : 0 }}"><div class="flex gap-2"><button type="button" data-close-modal class="rounded-lg border border-[#ced4da] bg-white px-4 py-2 text-[11px] font-semibold text-[#495057] transition hover:bg-[#e9ecef]">Cancel</button><button class="rounded-lg bg-[#0a1324] px-4 py-2 text-[11px] font-semibold text-white transition hover:bg-[#17243b]">Save changes</button></div></div>
            </form>
            </div>
        </div>
    @endforeach

    <script>
        (() => {
            const closeModal = (modal) => {
                modal.classList.remove('is-open');
                document.body.classList.remove('overflow-hidden');
            };
            document.querySelectorAll('[data-open-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const modal = document.getElementById(button.dataset.openModal);
                    if (!modal) return;
                    modal.classList.add('is-open');
                    document.body.classList.add('overflow-hidden');
                });
            });
            document.querySelectorAll('[data-close-modal]').forEach((button) => {
                button.addEventListener('click', () => closeModal(button.closest('[data-modal]')));
            });
            document.querySelectorAll('[data-modal]').forEach((modal) => {
                modal.addEventListener('click', (event) => { if (event.target === modal || event.target.closest('[data-modal-backdrop]')) closeModal(modal); });
            });
        })();
    </script>
@endcomponent
