@component('layouts.shell', ['active' => 'referrals', 'title' => 'Referrals'])
    <div class="mx-auto max-w-[1250px]">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-[#7b879a]">Invite people responsibly and track qualifying activity transparently.</p>
                <h2 class="display-copy mt-2 text-3xl font-semibold sm:text-4xl">Partner dashboard.</h2>
            </div><span
                class="inline-flex w-fit items-center gap-2 rounded-xl border border-[#cdebd8] bg-[#eaf9f0] px-3.5 py-3 text-[11px] font-semibold text-[#2f8d63]"><span
                    class="h-2 w-2 rounded-full bg-[#48c58b]"></span> Attribution protected</span>
        </div>
        @if (session('status'))
            <div class="mt-7 rounded-2xl border border-[#cdebd8] bg-[#eaf9f0] px-4 py-3 text-xs font-medium text-[#2f8d63]">
                {{ session('status') }}</div>
@endif
        @if ($errors->any())
                <div class="mt-7 rounded-2xl border border-[#ffd8cf] bg-[#fff0ec] px-4 py-3 text-xs text-[#b94f3a]">
                    {{ $errors->first() }}</div>
            @endif
            <section class="mt-8 grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
                <div class="rounded-[24px] bg-[#0a1324] p-6 text-white sm:p-8">
                    <div class="flex items-start justify-between gap-5">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[.18em] text-white/40">Your referral code
                            </p>
                            <p class="mt-3 font-mono text-3xl font-semibold tracking-[.12em] text-[#b9f7d2]">
                                {{ $user->referral_code }}</p>
                        </div><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#b9f7d2] text-[#0a1324]"><x-icon
                                name="users" size="18" /></span>
                    </div>
                    <p class="mt-5 max-w-xl text-xs leading-5 text-white/55">Share this secure link. Attribution is locked
                        to the first accepted referral and commissions are subject to the published referral terms.</p>
                    <div class="mt-5 rounded-xl bg-white/[.07] px-4 py-3 font-mono text-[11px] text-white/75">
                        {{ url('/ref/' . $user->referral_code) }}</div>
                </div>
                <div class="app-surface rounded-[24px] border border-[#e2e7ef] bg-white p-6 sm:p-8">
                    <p class="text-[10px] font-semibold uppercase tracking-[.18em] text-[#9aa5b7]">Already have a code?</p>
                    <h3 class="mt-3 text-lg font-semibold">Claim attribution.</h3>
                    <p class="mt-2 text-xs leading-5 text-[#7b879a]">You can claim a referral before qualifying activity
                        begins. Existing attribution cannot be replaced.</p>
                    @if ($user->referred_by_id === null)
                        <form action="{{ route('referrals.claim') }}" method="post" class="mt-6 flex gap-2">@csrf<input
                                name="referral_code" value="{{ old('referral_code') }}" required maxlength="32"
                                class="min-w-0 flex-1 rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 font-mono text-xs uppercase outline-none focus:border-[#7668f6]"
                                placeholder="REFERRAL CODE"><button
                                class="rounded-xl bg-[#7668f6] px-4 py-3 text-xs font-semibold text-white">Claim</button>
                    </form>@else<span
                            class="mt-6 inline-flex rounded-full bg-[#eaf9f0] px-3 py-2 text-[10px] font-semibold text-[#36a675]">Referral
                            attribution is active</span>
                    @endif
                </div>
            </section>
            <section class="mt-6 grid gap-5 xl:grid-cols-2">
                <div class="app-surface overflow-hidden rounded-[24px] border border-[#e2e7ef] bg-white">
                    <div class="border-b border-[#edf0f4] px-6 py-5 sm:px-7">
                        <h3 class="text-sm font-semibold">Referred users</h3>
                        <p class="mt-1 text-[11px] text-[#9aa5b7]">{{ $user->referrals_count }} direct referral(s)</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[520px] text-left">
                            <thead>
                                <tr class="text-[10px] uppercase tracking-[.14em] text-[#a3adbd]">
                                    <th class="px-6 py-4 font-semibold sm:px-7">User</th>
                                    <th class="px-4 py-4 font-semibold">Joined</th>
                                    <th class="px-6 py-4 text-right font-semibold sm:px-7">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f0f2f6]">
                                @forelse ($referrals as $referral)
                                    <tr>
                                        <td class="px-6 py-4 sm:px-7">
                                            <p class="text-xs font-semibold">{{ $referral->name }}</p>
                                            <p class="mt-1 text-[10px] text-[#a0aabb]">{{ $referral->email }}</p>
                                        </td>
                                        <td class="px-4 py-4 text-[11px] text-[#7b879a]">
                                            {{ $referral->created_at?->format('M d, Y') }}</td>
                                        <td class="px-6 py-4 text-right sm:px-7"><span
                                                class="rounded-full bg-[#eaf9f0] px-2.5 py-1 text-[10px] font-semibold text-[#36a675]">{{ $referral->isActive() ? 'Active' : 'Restricted' }}</span>
                                        </td>
                                </tr>@empty<tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-xs text-[#9aa5b7]">No direct
                                            referrals yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="app-surface overflow-hidden rounded-[24px] border border-[#e2e7ef] bg-white">
                    <div class="border-b border-[#edf0f4] px-6 py-5 sm:px-7">
                        <h3 class="text-sm font-semibold">Commission statement</h3>
                        <p class="mt-1 text-[11px] text-[#9aa5b7]">Pending commissions remain held until review.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[620px] text-left">
                            <thead>
                                <tr class="text-[10px] uppercase tracking-[.14em] text-[#a3adbd]">
                                    <th class="px-6 py-4 font-semibold sm:px-7">Trigger</th>
                                    <th class="px-4 py-4 font-semibold">Date</th>
                                    <th class="px-4 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 text-right font-semibold sm:px-7">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f0f2f6]">
                                @forelse ($commissions as $commission)
                                    <tr>
                                        <td class="px-6 py-4 text-xs font-semibold sm:px-7">
                                            {{ str($commission->trigger)->replace('_', ' ')->title() }}<p
                                                class="mt-1 text-[10px] font-normal text-[#a0aabb]">
                                                {{ $commission->referredUser?->name }}</p>
                                        </td>
                                        <td class="px-4 py-4 text-[11px] text-[#7b879a]">
                                            {{ $commission->created_at?->format('M d, Y') }}</td>
                                        <td class="px-4 py-4"><span
                                                class="rounded-full {{ $commission->status->value === 'released' ? 'bg-[#eaf9f0] text-[#36a675]' : ($commission->status->value === 'clawed_back' ? 'bg-[#fff0ec] text-[#ef735c]' : 'bg-[#fff7db] text-[#bd8b1b]') }} px-2.5 py-1 text-[10px] font-semibold">{{ str($commission->status->value)->replace('_', ' ')->title() }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-xs font-semibold text-[#0a1324]">
                                            {{ $commission->currency }}
                                            {{ number_format($commission->commission_amount_minor / 100, 2) }}</td>
                                </tr>@empty<tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-xs text-[#9aa5b7]">No
                                            commissions recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
    </div>
@endcomponent
