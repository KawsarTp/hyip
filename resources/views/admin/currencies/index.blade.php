@component('layouts.shell', ['admin' => true, 'active' => 'currencies', 'title' => 'Currencies'])
    <div class="mx-auto max-w-[1320px]">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-[#7b879a]">Configure supported settlement currencies, display formats, and conversion rates.</p>
                <h2 class="display-copy mt-2 text-3xl font-semibold sm:text-4xl">Currency control.</h2>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.languages.index') }}" class="rounded-xl border border-[#dfe5ec] bg-white px-4 py-3 text-[11px] font-semibold text-[#536078]">Manage languages →</a>
                <button type="button" data-open-modal="currency-create" class="rounded-xl bg-[#0a1324] px-4 py-3 text-[11px] font-semibold text-white">+ Add currency</button>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-7 rounded-2xl border border-[#cdebd8] bg-[#eaf9f0] px-4 py-3 text-xs font-medium text-[#2f8d63]">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-7 rounded-2xl border border-[#ffd8cf] bg-[#fff0ec] px-4 py-3 text-xs text-[#b94f3a]"><ul class="grid gap-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="app-surface mt-8 overflow-hidden rounded-[24px] border border-[#e2e7ef] bg-white">
            <div class="flex flex-col justify-between gap-4 border-b border-[#edf0f4] px-6 py-5 sm:flex-row sm:items-center sm:px-8"><div><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-[#9aa5b7]">Settlement configuration</p><h3 class="mt-2 text-xl font-semibold">Supported currencies</h3><p class="mt-2 text-xs leading-5 text-[#9aa5b7]">Rates are stored against the platform default currency. Update them before enabling production payments.</p></div><span class="rounded-xl bg-[#eaf9f0] px-3 py-2 text-[11px] font-semibold text-[#36a675]">{{ $currencies->where('is_enabled', true)->count() }} active</span></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[980px] text-left"><thead><tr class="border-b border-[#edf0f4] text-[10px] uppercase tracking-[.14em] text-[#a3adbd]"><th class="px-6 py-4 font-semibold sm:px-8">Currency</th><th class="px-4 py-4 font-semibold">Rate</th><th class="px-4 py-4 font-semibold">Display</th><th class="px-4 py-4 font-semibold">Status</th><th class="px-6 py-4 text-right font-semibold sm:px-8">Action</th></tr></thead><tbody class="divide-y divide-[#f0f2f6]">
                @forelse ($currencies as $currency)
                    <tr class="align-top"><td class="px-6 py-5 sm:px-8"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#eef3f8] text-xs font-bold text-[#536078]">{{ $currency->symbol }}</span><div><p class="text-xs font-semibold">{{ $currency->code }} <span class="font-normal text-[#9aa5b7]">· {{ $currency->name }}</span></p><p class="mt-1 text-[10px] text-[#a0aabb]">{{ $currency->rate_source ?: 'Manual rate' }}</p></div></div></td><td class="px-4 py-5 text-xs font-semibold">{{ $currency->rate_to_default }}<p class="mt-1 text-[10px] font-normal text-[#a0aabb]">to platform default</p></td><td class="px-4 py-5 text-xs text-[#7b879a]">{{ $currency->symbol_position === 'suffix' ? '1,234.56 '.$currency->symbol : $currency->symbol.'1,234.56' }}<p class="mt-1 text-[10px]">{{ $currency->decimal_places }} decimals</p></td><td class="px-4 py-5"><span class="rounded-full {{ $currency->is_enabled ? 'bg-[#eaf9f0] text-[#36a675]' : 'bg-[#f1f3f7] text-[#8b96a8]' }} px-2 py-1 text-[9px] font-semibold">{{ $currency->is_enabled ? 'Active' : 'Disabled' }}</span>@if ($currency->is_default)<span class="ml-1 rounded-full bg-[#f0efff] px-2 py-1 text-[9px] font-semibold text-[#7668f6]">Default</span>@endif</td><td class="px-6 py-5 text-right sm:px-8">@unless ($currency->is_default)<form action="{{ route('admin.currencies.default', $currency) }}" method="post" class="mb-2">@csrf @method('patch')<button class="text-[10px] font-semibold text-[#7668f6]">Make default</button></form>@endunless<button type="button" data-open-modal="currency-edit-{{ $currency->id }}" class="rounded-lg border border-[#dfe5ec] px-3 py-2 text-[10px] font-semibold text-[#536078]">Edit settings</button></td></tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-16 text-center text-xs text-[#9aa5b7]">No currencies configured yet.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>

    <div id="currency-create" data-modal role="dialog" aria-modal="true" aria-labelledby="currency-create-title" class="admin-modal">
        <div data-modal-backdrop class="admin-modal__backdrop"></div>
        <div class="admin-modal__card admin-modal__card--wide rounded-2xl border border-[#dee2e6] bg-white text-[#0a1324] shadow-[0_1rem_3rem_rgba(0,0,0,.2)]">
            <div class="flex items-start justify-between border-b border-[#dee2e6] px-5 py-4"><div><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-[#9aa5b7]">Settlement configuration</p><h3 id="currency-create-title" class="mt-1 text-lg font-semibold">Add currency</h3></div><button type="button" data-close-modal class="grid h-8 w-8 place-items-center rounded-lg text-xl leading-none text-[#6c757d] transition hover:bg-[#f1f3f5] hover:text-[#212529]" aria-label="Close">×</button></div>
        <form action="{{ route('admin.currencies.store') }}" method="post" class="grid gap-4 p-6">@csrf
            <div class="grid gap-4 sm:grid-cols-3"><div><label for="currency_code" class="text-[11px] font-semibold text-[#536078]">ISO code</label><input id="currency_code" name="code" required maxlength="3" placeholder="EUR" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm uppercase outline-none focus:border-[#7668f6]"></div><div><label for="currency_name" class="text-[11px] font-semibold text-[#536078]">Currency name</label><input id="currency_name" name="name" required placeholder="Euro" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm outline-none focus:border-[#7668f6]"></div><div><label for="currency_symbol" class="text-[11px] font-semibold text-[#536078]">Symbol</label><input id="currency_symbol" name="symbol" required maxlength="10" placeholder="€" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm outline-none focus:border-[#7668f6]"></div></div>
            <div class="grid gap-4 sm:grid-cols-4"><div><label for="currency_decimals" class="text-[11px] font-semibold text-[#536078]">Decimals</label><input id="currency_decimals" name="decimal_places" type="number" min="0" max="8" value="2" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm"></div><div><label for="currency_rate" class="text-[11px] font-semibold text-[#536078]">Rate to default</label><input id="currency_rate" name="rate_to_default" type="number" min="0.0000000001" step="0.0000000001" value="1" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm"></div><div><label for="currency_source" class="text-[11px] font-semibold text-[#536078]">Rate source</label><input id="currency_source" name="rate_source" placeholder="Manual / provider" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm"></div><div><label for="currency_position" class="text-[11px] font-semibold text-[#536078]">Symbol position</label><select id="currency_position" name="symbol_position" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm"><option value="prefix">Prefix</option><option value="suffix">Suffix</option></select></div></div>
            <div class="grid gap-4 sm:grid-cols-3"><div><label for="currency_thousands" class="text-[11px] font-semibold text-[#536078]">Thousands separator</label><input id="currency_thousands" name="thousands_separator" value="," maxlength="2" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm"></div><div><label for="currency_decimal" class="text-[11px] font-semibold text-[#536078]">Decimal separator</label><input id="currency_decimal" name="decimal_separator" value="." maxlength="2" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-2.5 text-sm"></div><div class="flex items-end"><input type="hidden" name="sort_order" value="0"><label class="flex items-center gap-2 pb-3 text-[11px] font-semibold text-[#536078]"><input type="checkbox" name="is_enabled" value="1" checked class="rounded border-[#cfd7e4] text-[#7668f6]"> Available to users</label></div></div>
            <div class="flex justify-end gap-2 border-t border-[#dee2e6] bg-[#f8f9fa] px-6 py-4"><button type="button" data-close-modal class="rounded-lg border border-[#ced4da] bg-white px-4 py-2 text-[11px] font-semibold text-[#495057] transition hover:bg-[#e9ecef]">Cancel</button><button class="rounded-lg bg-[#0a1324] px-5 py-2 text-[11px] font-semibold text-white transition hover:bg-[#17243b]">Save currency</button></div>
        </form>
        </div>
    </div>

    @foreach ($currencies as $currency)
        <div id="currency-edit-{{ $currency->id }}" data-modal role="dialog" aria-modal="true" aria-labelledby="currency-edit-title-{{ $currency->id }}" class="admin-modal">
            <div data-modal-backdrop class="admin-modal__backdrop"></div>
            <div class="admin-modal__card admin-modal__card--wide rounded-2xl border border-[#dee2e6] bg-white text-[#0a1324] shadow-[0_1rem_3rem_rgba(0,0,0,.2)]">
                <div class="flex items-start justify-between border-b border-[#dee2e6] px-5 py-4"><div><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-[#9aa5b7]">Currency settings</p><h3 id="currency-edit-title-{{ $currency->id }}" class="mt-1 text-lg font-semibold">{{ $currency->code }} · {{ $currency->name }}</h3></div><button type="button" data-close-modal class="grid h-8 w-8 place-items-center rounded-lg text-xl leading-none text-[#6c757d] transition hover:bg-[#f1f3f5] hover:text-[#212529]" aria-label="Close">×</button></div>
            <form action="{{ route('admin.currencies.update', $currency) }}" method="post" class="grid gap-4 p-6">@csrf @method('patch')
                <div class="grid gap-4 sm:grid-cols-3"><div><label class="text-[11px] font-semibold text-[#536078]">ISO code</label><input name="code" value="{{ $currency->code }}" required maxlength="3" class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm uppercase"></div><div><label class="text-[11px] font-semibold text-[#536078]">Currency name</label><input name="name" value="{{ $currency->name }}" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"></div><div><label class="text-[11px] font-semibold text-[#536078]">Symbol</label><input name="symbol" value="{{ $currency->symbol }}" required maxlength="10" class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"></div></div>
                <div class="grid gap-4 sm:grid-cols-4"><div><label class="text-[11px] font-semibold text-[#536078]">Decimals</label><input name="decimal_places" type="number" min="0" max="8" value="{{ $currency->decimal_places }}" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"></div><div><label class="text-[11px] font-semibold text-[#536078]">Rate to default</label><input name="rate_to_default" type="number" step="0.0000000001" min="0.0000000001" value="{{ $currency->rate_to_default }}" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"></div><div><label class="text-[11px] font-semibold text-[#536078]">Rate source</label><input name="rate_source" value="{{ $currency->rate_source }}" class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"></div><div><label class="text-[11px] font-semibold text-[#536078]">Symbol position</label><select name="symbol_position" class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"><option value="prefix" @selected($currency->symbol_position === 'prefix')>Prefix</option><option value="suffix" @selected($currency->symbol_position === 'suffix')>Suffix</option></select></div></div>
                <div class="grid gap-4 sm:grid-cols-3"><div><label class="text-[11px] font-semibold text-[#536078]">Thousands separator</label><input name="thousands_separator" value="{{ $currency->thousands_separator }}" maxlength="2" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"></div><div><label class="text-[11px] font-semibold text-[#536078]">Decimal separator</label><input name="decimal_separator" value="{{ $currency->decimal_separator }}" maxlength="2" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] px-3 py-2.5 text-sm"></div><div class="flex items-end"><label class="flex items-center gap-2 pb-3 text-[11px] font-semibold text-[#536078]"><input type="checkbox" name="is_enabled" value="1" @checked($currency->is_enabled) class="rounded border-[#cfd7e4] text-[#7668f6]"> Available to users</label></div></div>
                <div class="flex justify-end gap-2 border-t border-[#dee2e6] bg-[#f8f9fa] px-6 py-4"><input type="hidden" name="sort_order" value="{{ $currency->sort_order }}"><input type="hidden" name="is_default" value="{{ $currency->is_default ? 1 : 0 }}"><button type="button" data-close-modal class="rounded-lg border border-[#ced4da] bg-white px-4 py-2 text-[11px] font-semibold text-[#495057] transition hover:bg-[#e9ecef]">Cancel</button><button class="rounded-lg bg-[#0a1324] px-5 py-2 text-[11px] font-semibold text-white transition hover:bg-[#17243b]">Save changes</button></div>
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
