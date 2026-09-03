@component('layouts.shell', ['active' => 'deposits', 'title' => 'New deposit'])
    <div class="mx-auto max-w-[1040px]">
        <a href="{{ route('deposits.index') }}" class="text-xs font-semibold text-[#7668f6]">← Back to deposits</a>
        <div class="mt-4 grid gap-6 lg:grid-cols-[1.15fr_.85fr]">
            <section class="app-surface rounded-[24px] border border-[#e2e7ef] bg-white p-6 sm:p-8">
                <p class="text-[10px] font-semibold uppercase tracking-[.18em] text-[#9aa5b7]">Secure funding</p>
                <h2 class="display-copy mt-3 text-3xl font-semibold">Start a deposit.</h2>
                <p class="mt-2 text-sm leading-6 text-[#7b879a]">Choose an amount and an enabled payment gateway. Your available wallet changes only after settlement.</p>

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-[#ffd8cf] bg-[#fff0ec] px-4 py-3 text-xs text-[#b94f3a]"><ul class="grid gap-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                @if ($gateways->isEmpty())
                    <div class="mt-8 rounded-2xl border border-[#f0dfb2] bg-[#fffaf0] p-5 text-xs leading-5 text-[#806e67]"><span class="font-semibold text-[#0a1324]">No payment gateway is available.</span> The operations team must enable a gateway before deposits can be created.</div>
                @else
                    <form action="{{ route('deposits.store') }}" method="post" class="mt-8 grid gap-5">
                        @csrf
                        <div class="grid gap-5 sm:grid-cols-[1fr_180px]"><div><label for="amount" class="text-[11px] font-semibold text-[#536078]">Amount</label><div class="mt-2 flex items-center rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 focus-within:border-[#7668f6]"><span id="deposit-symbol" class="text-sm font-semibold text-[#9aa5b7]">{{ $currencies->firstWhere('code', old('currency', $currency))?->symbol }}</span><input id="amount" name="amount" value="{{ old('amount') }}" required inputmode="decimal" placeholder="0.00" class="w-full border-0 bg-transparent px-2 py-3 text-sm font-semibold outline-none ring-0" /></div></div><div><label for="currency" class="text-[11px] font-semibold text-[#536078]">Currency</label><select id="currency" name="currency" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 text-sm font-semibold uppercase outline-none focus:border-[#7668f6]">@foreach ($currencies as $availableCurrency)<option value="{{ $availableCurrency->code }}" data-symbol="{{ $availableCurrency->symbol }}" @selected(old('currency', $currency) === $availableCurrency->code)>{{ $availableCurrency->code }} · {{ $availableCurrency->name }}</option>@endforeach</select></div></div>
                        <div><p class="text-[11px] font-semibold text-[#536078]">Payment gateway</p><div class="mt-2 grid gap-3">
                            @foreach ($gateways as $gateway)
                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-[#e2e7ef] bg-[#fbfcfe] p-4 transition has-[:checked]:border-[#7668f6] has-[:checked]:bg-[#f8f7ff]"><input type="radio" name="payment_gateway_id" value="{{ $gateway->id }}" @checked(old('payment_gateway_id', $gateways->first()->id) == $gateway->id) class="mt-1 border-[#cfd7e4] text-[#7668f6]"><span class="min-w-0 flex-1"><span class="flex items-center justify-between gap-3"><span class="text-sm font-semibold text-[#0a1324]">{{ $gateway->name }}</span><span class="rounded-full bg-[#eaf9f0] px-2 py-1 text-[9px] font-semibold text-[#36a675]">{{ $gateway->is_test_mode ? 'Test' : 'Live' }}</span></span><span class="mt-1 block text-[11px] capitalize text-[#7b879a]">{{ str($gateway->method)->replace('_', ' ')->title() }} · {{ $gateway->driver }}</span>
                                    @if ($gateway->driver === 'bank_transfer')<span class="mt-2 block text-[10px] text-[#a0aabb]">{{ $gateway->bankAccounts->count() }} receiving account(s) configured</span>@elseif ($gateway->settings['instructions'] ?? false)<span class="mt-2 block text-[10px] text-[#a0aabb]">{{ str($gateway->settings['instructions'])->limit(90) }}</span>@endif
                                </span></label>
                            @endforeach
                        </div></div>
                        <button class="mt-2 inline-flex items-center justify-center gap-2 rounded-xl bg-[#0a1324] px-4 py-3.5 text-xs font-semibold text-white transition hover:bg-[#182942]">Create payment instructions <x-icon name="arrow-right" size="14" class="text-[#b9f7d2]" /></button>
                    </form>
                @endif
            </section>
            <aside class="rounded-[24px] bg-[#0a1324] p-6 text-white sm:p-8"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#b9f7d2] text-[#0a1324]"><x-icon name="shield" size="18" /></span><h3 class="mt-6 text-lg font-semibold">Protected by design.</h3><div class="mt-5 grid gap-4 text-xs leading-5 text-white/55"><p><span class="font-semibold text-white">1.</span> A unique payment reference is created for your selected gateway.</p><p><span class="font-semibold text-white">2.</span> The payment stays pending until the provider or finance team confirms it.</p><p><span class="font-semibold text-white">3.</span> Settlement posts one balanced ledger entry to your available wallet.</p></div><div class="mt-8 border-t border-white/10 pt-5 text-[11px] text-white/40">Never send funds without matching the instructions and reference on your deposit receipt.</div></aside>
        </div>
    </div>
    <script>
        document.getElementById('currency')?.addEventListener('change', (event) => {
            document.getElementById('deposit-symbol').textContent = event.target.selectedOptions[0]?.dataset.symbol || '';
        });
    </script>
@endcomponent
