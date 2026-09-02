@component('layouts.shell', ['active' => 'wallet', 'title' => 'Wallet'])
    <div class="mx-auto max-w-[1440px]">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div><p class="text-sm text-[#7b879a]">Every balance is backed by an auditable journal.</p><h2 class="display-copy mt-2 text-3xl font-semibold sm:text-4xl">Your money, clearly accounted for.</h2></div>
            <span class="inline-flex w-fit items-center gap-2 rounded-xl border border-[#cdebd8] bg-[#eaf9f0] px-3.5 py-3 text-[11px] font-semibold text-[#2f8d63]"><span class="h-2 w-2 rounded-full bg-[#48c58b]"></span> Ledger protected</span>
        </div>

        <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($wallets as $wallet)
                @php($isAvailable = $wallet->type->value === 'available')
                <div class="app-surface rounded-[22px] {{ $isAvailable ? 'bg-[#0a1324] text-white' : 'border border-[#e2e7ef] bg-white' }} p-5">
                    <div class="flex items-center justify-between"><p class="text-[10px] font-semibold uppercase tracking-[.14em] {{ $isAvailable ? 'text-white/45' : 'text-[#9aa5b7]' }}">{{ $wallet->type->name }}</p><span class="grid h-8 w-8 place-items-center rounded-lg {{ $isAvailable ? 'bg-white/10 text-[#b9f7d2]' : 'bg-[#f0efff] text-[#7668f6]' }}"><x-icon name="wallet" size="14" /></span></div>
                    <p class="mt-5 text-2xl font-semibold tracking-[-.06em]">{{ $wallet->currency }} {{ number_format($wallet->balance_minor / 100, 2) }}</p>
                    <p class="mt-2 text-[10px] {{ $isAvailable ? 'text-white/35' : 'text-[#a0aabb]' }}">{{ $isAvailable ? 'Ready to allocate or withdraw' : 'Protected balance bucket' }}</p>
                </div>
            @endforeach
        </section>

        <section class="mt-8 grid gap-5 xl:grid-cols-[1.35fr_1fr]">
            <div class="app-surface overflow-hidden rounded-[24px] border border-[#e2e7ef] bg-white">
                <div class="flex items-center justify-between border-b border-[#edf0f4] px-6 py-5 sm:px-7"><div><h3 class="text-sm font-semibold tracking-[-.02em]">Wallet activity</h3><p class="mt-1 text-[11px] text-[#9aa5b7]">Posted entries touching your wallets</p></div><span class="rounded-lg bg-[#f5f7fb] px-2.5 py-2 text-[10px] font-semibold text-[#7b879a]">Minor-unit ledger</span></div>
                <div class="overflow-x-auto"><table class="w-full min-w-[650px] text-left"><thead><tr class="text-[10px] uppercase tracking-[.14em] text-[#a3adbd]"><th class="px-6 py-4 font-semibold sm:px-7">Entry</th><th class="px-4 py-4 font-semibold">Type</th><th class="px-4 py-4 font-semibold">Date</th><th class="px-6 py-4 text-right font-semibold sm:px-7">Amount</th></tr></thead><tbody class="divide-y divide-[#f0f2f6]">
                    @forelse ($entries as $entry)
                        @php($line = $entry->lines->first(fn ($line) => $line->account?->user_id === auth()->id()))
                        @php($amountMinor = ($line?->credit_minor ?? 0) - ($line?->debit_minor ?? 0))
                        <tr><td class="px-6 py-4 sm:px-7"><p class="text-xs font-semibold">{{ $entry->description }}</p><p class="mt-1 font-mono text-[10px] text-[#a0aabb]">{{ str($entry->reference)->limit(18) }}</p></td><td class="px-4 py-4 text-[11px] capitalize text-[#7b879a]">{{ $entry->type->value }}</td><td class="px-4 py-4 text-[11px] text-[#7b879a]">{{ $entry->posted_at?->format('M d, Y · H:i') }}</td><td class="px-6 py-4 text-right text-xs font-semibold {{ $amountMinor >= 0 ? 'text-[#36a675]' : 'text-[#ef735c]' }}">{{ $amountMinor >= 0 ? '+' : '−' }}{{ $entry->currency }} {{ number_format(abs($amountMinor) / 100, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-16 text-center"><span class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-[#f0efff] text-[#7668f6]"><x-icon name="receipt" size="18" /></span><p class="mt-4 text-sm font-semibold">No wallet activity yet</p><p class="mt-1 text-xs text-[#9aa5b7]">Deposits, returns, and payouts will appear here after they are posted.</p></td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
            <div class="app-surface rounded-[24px] border border-[#e2e7ef] bg-white p-6 sm:p-7"><div class="flex items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#eaf9f0] text-[#36a675]"><x-icon name="shield" size="17" /></span><div><h3 class="text-sm font-semibold">How wallet balances work</h3><p class="mt-2 text-xs leading-6 text-[#7b879a]">Arcadia records each movement as a balanced journal entry. Your displayed wallet balance is a fast cache of that journal, protected by database locks during every update.</p></div></div><div class="mt-6 grid gap-3 border-t border-[#edf0f4] pt-5"><div class="flex items-center justify-between text-[11px]"><span class="text-[#9aa5b7]">Currencies</span><span class="font-semibold text-[#0a1324]">USD · more coming</span></div><div class="flex items-center justify-between text-[11px]"><span class="text-[#9aa5b7]">Entry protection</span><span class="font-semibold text-[#36a675]">Balanced + idempotent</span></div><div class="flex items-center justify-between text-[11px]"><span class="text-[#9aa5b7]">Deposit status</span><span class="font-semibold text-[#bd8b1b]">Payment gateway next</span></div></div></div>
        </section>
    </div>
@endcomponent
