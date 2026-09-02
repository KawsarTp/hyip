@component('layouts.shell', ['admin' => true, 'active' => 'referrals', 'title' => 'Referral settings'])
    <div class="mx-auto max-w-[820px]">
        <a href="{{ route('admin.referrals.index') }}" class="text-xs font-semibold text-[#7668f6]">← Back to referral
            operations</a>
        <section class="app-surface mt-5 rounded-[24px] border border-[#e2e7ef] bg-white p-6 sm:p-8">
            <p class="text-[10px] font-semibold uppercase tracking-[.18em] text-[#9aa5b7]">Commission policy</p>
            <h2 class="display-copy mt-3 text-3xl font-semibold">Set safe referral terms.</h2>
            <p class="mt-2 text-sm leading-6 text-[#7b879a]">Commission is created only for an attributed user’s qualifying
                investment and remains held for the anti-abuse period.</p>
            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-[#ffd8cf] bg-[#fff0ec] px-4 py-3 text-xs text-[#b94f3a]">
                    {{ $errors->first() }}</div>
            @endif
            <form action="{{ route('admin.referrals.settings.update') }}" method="post" class="mt-8 grid gap-5">@csrf
                @method('patch')<div class="grid gap-5 sm:grid-cols-3">
                    <div><label for="investment_commission" class="text-[11px] font-semibold text-[#536078]">Investment
                            commission</label>
                        <div class="mt-2 flex items-center rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3"><input
                                id="investment_commission" name="investment_commission"
                                value="{{ old('investment_commission', number_format($settings->investment_commission_bps / 100, 2, '.', '')) }}"
                                required class="w-full border-0 bg-transparent py-3 text-sm outline-none ring-0"><span
                                class="text-xs text-[#9aa5b7]">%</span></div>
                    </div>
                    <div><label for="hold_days" class="text-[11px] font-semibold text-[#536078]">Hold days</label><input
                            id="hold_days" name="hold_days" type="number" min="0"
                            value="{{ old('hold_days', $settings->hold_days) }}" required
                            class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 text-sm outline-none focus:border-[#7668f6]">
                    </div>
                    <div><label for="minimum_qualifying_investment" class="text-[11px] font-semibold text-[#536078]">Minimum
                            investment</label><input id="minimum_qualifying_investment" name="minimum_qualifying_investment"
                            value="{{ old('minimum_qualifying_investment', number_format($settings->minimum_qualifying_investment_minor / 100, 2, '.', '')) }}"
                            required
                            class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 text-sm outline-none focus:border-[#7668f6]">
                    </div>
                </div><label
                    class="flex items-center gap-2 border-t border-[#edf0f4] pt-5 text-xs font-semibold text-[#536078]"><input
                        type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $settings->is_enabled))
                        class="rounded border-[#cfd7e4] text-[#7668f6]"> Enable investment referral commissions for new
                    qualifying investments</label><button
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0a1324] px-4 py-3.5 text-xs font-semibold text-white">Save
                    referral policy <x-icon name="check" size="14" class="text-[#b9f7d2]" /></button></form>
        </section>
    </div>
@endcomponent
