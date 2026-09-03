@component('layouts.shell', ['admin' => true, 'active' => 'languages', 'title' => 'Edit translations'])
    <div class="mx-auto max-w-[1200px]">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><a href="{{ route('admin.languages.index') }}" class="text-xs font-semibold text-[#7668f6]">← Back to languages</a><p class="mt-5 text-sm text-[#7b879a]">Edit every interface string for {{ $language->native_name }} ({{ $language->code }}).</p><h2 class="display-copy mt-2 text-3xl font-semibold sm:text-4xl">Translation workspace.</h2></div><span class="rounded-xl border border-[#e2e7ef] bg-white px-3.5 py-3 text-[11px] font-semibold text-[#536078]">{{ $translations->count() }} keys</span></div>
        @if (session('status'))<div class="mt-7 rounded-2xl border border-[#cdebd8] bg-[#eaf9f0] px-4 py-3 text-xs font-medium text-[#2f8d63]">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mt-7 rounded-2xl border border-[#ffd8cf] bg-[#fff0ec] px-4 py-3 text-xs text-[#b94f3a]"><ul class="grid gap-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form action="{{ route('admin.languages.translations.update', $language) }}" method="post" class="app-surface mt-8 rounded-[24px] border border-[#e2e7ef] bg-white p-6 sm:p-8">@csrf @method('patch')
            <div class="flex flex-col justify-between gap-4 border-b border-[#edf0f4] pb-5 sm:flex-row sm:items-center"><div><h3 class="text-sm font-semibold">String catalog</h3><p class="mt-1 text-[11px] text-[#9aa5b7]">Leave a value blank to remove its override and use the fallback language.</p></div><button type="button" data-add-translation class="rounded-xl border border-[#dfe5ec] px-4 py-2.5 text-[11px] font-semibold text-[#536078]">+ Add translation key</button></div>
            <div data-translation-list class="mt-6 grid gap-3">@foreach ($translations as $index => $translation)<div class="grid gap-3 rounded-2xl border border-[#edf0f4] bg-[#fbfcfe] p-4 sm:grid-cols-[130px_1fr_1.5fr_auto]"><div><label class="text-[10px] font-semibold uppercase tracking-[.12em] text-[#a0aabb]">Group</label><input name="translations[{{ $index }}][group_name]" value="{{ $translation->group_name }}" required class="mt-2 w-full rounded-lg border border-[#dfe5ec] bg-white px-2.5 py-2 text-xs"></div><div><label class="text-[10px] font-semibold uppercase tracking-[.12em] text-[#a0aabb]">Key</label><input name="translations[{{ $index }}][key]" value="{{ $translation->key }}" required class="mt-2 w-full rounded-lg border border-[#dfe5ec] bg-white px-2.5 py-2 text-xs"></div><div><label class="text-[10px] font-semibold uppercase tracking-[.12em] text-[#a0aabb]">Translated value</label><textarea name="translations[{{ $index }}][value]" rows="2" class="mt-2 w-full rounded-lg border border-[#dfe5ec] bg-white px-2.5 py-2 text-xs">{{ $translation->value }}</textarea></div><button type="button" data-remove-translation class="self-end rounded-lg px-2 py-2 text-xs text-[#b94f3a]" aria-label="Remove translation">×</button></div>@endforeach</div>
            <div class="mt-6 flex justify-end border-t border-[#edf0f4] pt-5"><button class="rounded-xl bg-[#0a1324] px-5 py-3 text-xs font-semibold text-white">Save translations</button></div>
        </form>
    </div>
    <template data-translation-template><div class="grid gap-3 rounded-2xl border border-[#edf0f4] bg-[#fbfcfe] p-4 sm:grid-cols-[130px_1fr_1.5fr_auto]"><div><label class="text-[10px] font-semibold uppercase tracking-[.12em] text-[#a0aabb]">Group</label><input data-field="group_name" required class="mt-2 w-full rounded-lg border border-[#dfe5ec] bg-white px-2.5 py-2 text-xs" value="messages"></div><div><label class="text-[10px] font-semibold uppercase tracking-[.12em] text-[#a0aabb]">Key</label><input data-field="key" required placeholder="navigation.overview" class="mt-2 w-full rounded-lg border border-[#dfe5ec] bg-white px-2.5 py-2 text-xs"></div><div><label class="text-[10px] font-semibold uppercase tracking-[.12em] text-[#a0aabb]">Translated value</label><textarea data-field="value" rows="2" class="mt-2 w-full rounded-lg border border-[#dfe5ec] bg-white px-2.5 py-2 text-xs"></textarea></div><button type="button" data-remove-translation class="self-end rounded-lg px-2 py-2 text-xs text-[#b94f3a]" aria-label="Remove translation">×</button></div></template>
    <script>
        (() => {
            const list = document.querySelector('[data-translation-list]');
            const template = document.querySelector('[data-translation-template]');
            const add = document.querySelector('[data-add-translation]');
            if (!list || !template || !add) return;
            let index = {{ $translations->count() }};
            add.addEventListener('click', () => {
                const row = template.content.cloneNode(true);
                row.querySelectorAll('[data-field]').forEach((field) => { field.name = 'translations[' + index + '][' + field.dataset.field + ']'; });
                list.appendChild(row);
                index += 1;
            });
            list.addEventListener('click', (event) => { if (event.target.closest('[data-remove-translation]')) event.target.closest('[data-remove-translation]').remove(); });
        })();
    </script>
@endcomponent
