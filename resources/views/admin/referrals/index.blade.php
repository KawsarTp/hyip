@component('layouts.shell', ['admin' => true, 'active' => 'referrals', 'title' => 'Referral commissions'])
    <div class="mx-auto max-w-[1400px]">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-[#7b879a]">Review attribution, anti-abuse holds, and commission ledger postings.</p>
                <h2 class="display-copy mt-2 text-3xl font-semibold sm:text-4xl">Referral operations.</h2>
            </div><a href="{{ route('admin.referrals.settings') }}"
                class="inline-flex w-fit items-center gap-2 rounded-xl border border-[#dfe5ec] bg-white px-4 py-3 text-xs font-semibold text-[#536078]">Referral
                settings <x-icon name="settings" size="14" class="text-[#7668f6]" /></a>
        </div>
        @if (session('status'))
            <div class="mt-7 rounded-2xl border border-[#cdebd8] bg-[#eaf9f0] px-4 py-3 text-xs font-medium text-[#2f8d63]">
                {{ session('status') }}</div>
        @endif
        <div class="mt-8 flex flex-wrap gap-2">
            <a href="{{ route('admin.referrals.index') }}"
                class="rounded-xl px-3 py-2 text-[11px] font-semibold {{ $status === '' ? 'bg-[#0a1324] text-white' : 'bg-white text-[#7b879a]' }}">All</a>
            @foreach (['pending', 'approved', 'released', 'held', 'clawed_back'] as $filter)
                <a href="{{ route('admin.referrals.index', ['status' => $filter]) }}"
                    class="rounded-xl px-3 py-2 text-[11px] font-semibold {{ $status === $filter ? 'bg-[#0a1324] text-white' : 'bg-white text-[#7b879a]' }}">{{ str($filter)->replace('_', ' ')->title() }}</a>
            @endforeach
        </div>
        <section class="app-surface mt-4 overflow-hidden rounded-[24px] border border-[#e2e7ef] bg-white">
            <div class="border-b border-[#edf0f4] px-6 py-5 sm:px-7">
                <h3 class="text-sm font-semibold">Commission queue</h3>
                <p class="mt-1 text-[11px] text-[#9aa5b7]">Commissions are not spendable until approved and released.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-[.14em] text-[#a3adbd]">
                            <th class="px-6 py-4 font-semibold sm:px-7">Referrer</th>
                            <th class="px-4 py-4 font-semibold">Qualified user</th>
                            <th class="px-4 py-4 font-semibold">Trigger</th>
                            <th class="px-4 py-4 font-semibold">Hold until</th>
                            <th class="px-4 py-4 font-semibold">Amount</th>
                            <th class="px-4 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 text-right font-semibold sm:px-7">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f2f6]">
                        @forelse ($commissions as $commission)
                            <tr>
                                <td class="px-6 py-4 sm:px-7">
                                    <p class="text-xs font-semibold">{{ $commission->referrer->name }}</p>
                                    <p class="mt-1 text-[10px] text-[#a0aabb]">{{ $commission->referrer->referral_code }}
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-xs font-semibold">{{ $commission->referredUser->name }}</td>
                                <td class="px-4 py-4 text-[11px] text-[#7b879a]">
                                    {{ str($commission->trigger)->replace('_', ' ')->title() }}</td>
                                <td class="px-4 py-4 text-[11px] text-[#7b879a]">
                                    {{ $commission->hold_until?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-4 text-xs font-semibold text-[#36a675]">{{ $commission->currency }}
                                    {{ number_format($commission->commission_amount_minor / 100, 2) }}</td>
                                <td class="px-4 py-4"><span
                                        class="rounded-full {{ $commission->status->value === 'released' ? 'bg-[#eaf9f0] text-[#36a675]' : ($commission->status->value === 'clawed_back' ? 'bg-[#fff0ec] text-[#ef735c]' : 'bg-[#fff7db] text-[#bd8b1b]') }} px-2.5 py-1 text-[10px] font-semibold">{{ str($commission->status->value)->replace('_', ' ')->title() }}</span>
                                </td>
                                <td class="px-6 py-4 text-right sm:px-7">
                                    @if (in_array($commission->status->value, ['pending', 'held']))
                                        <form action="{{ route('admin.referrals.review', $commission) }}" method="post"
                                            class="inline">@csrf @method('patch')<button name="decision" value="approved"
                                                class="rounded-lg bg-[#7668f6] px-3 py-2 text-[10px] font-semibold text-white">Approve</button>
                                        </form>
                                    @elseif ($commission->status->value === 'approved')
                                        <form action="{{ route('admin.referrals.review', $commission) }}" method="post"
                                            class="inline">@csrf @method('patch')<button name="decision" value="released"
                                                class="rounded-lg bg-[#36a675] px-3 py-2 text-[10px] font-semibold text-white">Release</button>
                                        </form>
                                    @endif
                                    @if (in_array($commission->status->value, ['pending', 'held', 'approved', 'released']))
                                            <form action="{{ route('admin.referrals.review', $commission) }}"
                                                method="post" class="ml-2 inline">@csrf @method('patch')<input
                                                    type="hidden" name="review_notes" value="Admin clawback review"><button
                                                    name="decision" value="clawed_back"
                                                    class="text-[10px] font-semibold text-[#b94f3a]">Claw back</button>
                                            </form>
                                        @endif
                                </td>
                            </tr>@empty<tr>
                                    <td colspan="7" class="px-6 py-16 text-center text-xs text-[#9aa5b7]">No referral
                                        commissions match this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($commissions->hasPages())
                    <div class="border-t border-[#edf0f4] px-6 py-4">{{ $commissions->links() }}</div>
                @endif
            </section>
        </div>
    @endcomponent
