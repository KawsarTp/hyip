@props([
    'admin' => false,
    'active' => 'overview',
    'title' => 'Overview',
])

@php
    $currentUser = auth()->user();

    $navItems = $admin
        ? [
            ['key' => 'overview', 'label' => 'Overview', 'icon' => 'grid', 'href' => route('admin.dashboard')],
            ['key' => 'users', 'label' => 'Users', 'icon' => 'users', 'href' => '#'],
            ['key' => 'investments', 'label' => 'Investments', 'icon' => 'briefcase', 'href' => route('admin.investments.index')],
            ['key' => 'plans', 'label' => 'Investment plans', 'icon' => 'spark', 'href' => route('admin.investment-plans.index')],
            ['key' => 'referrals', 'label' => 'Referrals', 'icon' => 'users', 'href' => route('admin.referrals.index')],
            ['key' => 'deposits', 'label' => 'Deposits', 'icon' => 'arrow-down', 'href' => route('admin.deposits.index')],
            ['key' => 'payouts', 'label' => 'Payouts', 'icon' => 'arrow-up', 'href' => route('admin.withdrawals.index')],
            ['key' => 'compliance', 'label' => 'Compliance', 'icon' => 'shield', 'href' => route('admin.kyc.index'), 'badge' => '3'],
            ['key' => 'ledger', 'label' => 'Ledger', 'icon' => 'receipt', 'href' => route('admin.ledger.index')],
            ['key' => 'audit', 'label' => 'Audit logs', 'icon' => 'shield', 'href' => route('admin.audit.index')],
            ['key' => 'payment-gateways', 'label' => 'Payment methods', 'icon' => 'settings', 'href' => route('admin.payment-gateways.index')],
            ['key' => 'withdrawal-methods', 'label' => 'Payout methods', 'icon' => 'arrow-up', 'href' => route('admin.withdrawal-methods.index')],
            ['key' => 'reports', 'label' => 'Reports', 'icon' => 'receipt', 'href' => '#'],
        ]
        : [
            ['key' => 'overview', 'label' => 'Overview', 'icon' => 'grid', 'href' => route('dashboard')],
            ['key' => 'investments', 'label' => 'My investments', 'icon' => 'briefcase', 'href' => route('investments.index')],
            ['key' => 'wallet', 'label' => 'Wallet', 'icon' => 'wallet', 'href' => route('wallet.index')],
            ['key' => 'deposits', 'label' => 'Deposit funds', 'icon' => 'arrow-down', 'href' => route('deposits.create')],
            ['key' => 'withdrawals', 'label' => 'Withdraw funds', 'icon' => 'arrow-up', 'href' => route('withdrawals.create')],
            ['key' => 'activity', 'label' => 'Activity', 'icon' => 'activity', 'href' => '#'],
            ['key' => 'referrals', 'label' => 'Referrals', 'icon' => 'users', 'href' => route('referrals.index')],
            ['key' => 'verification', 'label' => 'Verification', 'icon' => 'shield', 'href' => route('kyc.edit')],
        ];

    $secondaryItems = $admin
        ? [
            ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'href' => '#'],
            ['key' => 'support', 'label' => 'Support', 'icon' => 'help', 'href' => '#'],
        ]
        : [
            ['key' => 'statements', 'label' => 'Statements', 'icon' => 'file', 'href' => '#'],
            ['key' => 'support', 'label' => 'Help & support', 'icon' => 'help', 'href' => '#'],
            ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'href' => '#'],
        ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a1324">
        <title>{{ $title }} · Arcadia</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f5f7fb] antialiased">
        <div data-sidebar-backdrop class="pointer-events-none fixed inset-0 z-30 bg-[#07101f]/50 opacity-0 transition-opacity lg:hidden"></div>

        <aside data-sidebar class="fixed inset-y-0 left-0 z-40 flex w-[272px] -translate-x-full flex-col bg-[#0a1324] text-white transition-transform duration-300 lg:translate-x-0">
            <div class="flex h-24 items-center gap-3 px-7">
                <a href="{{ $admin ? route('admin.dashboard') : route('dashboard') }}" class="flex items-center gap-3" aria-label="Arcadia home">
                    <span class="grid h-10 w-10 place-items-center rounded-[14px] bg-[#b9f7d2] text-[#0a1324] shadow-[0_8px_25px_rgba(185,247,210,.2)]">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"><path d="M4 17.5 10.5 4l3.2 7h6.3L16 17.5H4Z"/><path d="m13.7 11 2.7 6.5"/></svg>
                    </span>
                    <span class="text-[21px] font-semibold tracking-[-0.06em]">arcadia<span class="text-[#b9f7d2]">.</span></span>
                </a>
            </div>

            <div class="px-5 pb-4">
                <div class="rounded-2xl border border-white/10 bg-white/[.06] px-4 py-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-semibold uppercase tracking-[.18em] text-white/40">Workspace</span>
                        <span class="h-2 w-2 rounded-full bg-[#b9f7d2] shadow-[0_0_12px_#b9f7d2]"></span>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-white/90">{{ $admin ? 'Operations desk' : 'Personal wealth' }}</span>
                        <x-icon name="chevron" size="14" class="rotate-90 text-white/40" />
                    </div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-4" aria-label="Primary navigation">
                <p class="px-3 pb-3 pt-4 text-[10px] font-semibold uppercase tracking-[.18em] text-white/30">{{ $admin ? 'Operations' : 'Your space' }}</p>
                <div class="grid gap-1">
                    @foreach ($navItems as $item)
                        <a href="{{ $item['href'] }}" class="group flex items-center justify-between rounded-xl px-3 py-3 text-[13px] font-medium transition {{ $active === $item['key'] ? 'bg-white text-[#0a1324] shadow-lg shadow-black/10' : 'text-white/55 hover:bg-white/[.07] hover:text-white' }}">
                            <span class="flex items-center gap-3"><x-icon name="{{ $item['icon'] }}" size="17" />{{ $item['label'] }}</span>
                            @if (isset($item['badge']))
                                <span class="rounded-md {{ $active === $item['key'] ? 'bg-[#e9eff7] text-[#536078]' : 'bg-white/10 text-white/45' }} px-1.5 py-0.5 text-[10px] font-semibold">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>

                <p class="px-3 pb-3 pt-9 text-[10px] font-semibold uppercase tracking-[.18em] text-white/30">Manage</p>
                <div class="grid gap-1">
                    @foreach ($secondaryItems as $item)
                        <a href="{{ $item['href'] }}" class="group flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-medium text-white/55 transition hover:bg-white/[.07] hover:text-white"><x-icon name="{{ $item['icon'] }}" size="17" />{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </nav>

            <div class="p-5">
                <div class="relative overflow-hidden rounded-2xl bg-[#182942] p-4">
                    <div class="noise absolute inset-0"></div>
                    <div class="relative">
                        <div class="mb-3 flex h-8 w-8 items-center justify-center rounded-lg bg-[#ff836d] text-[#0a1324]"><x-icon name="spark" size="15" /></div>
                        <p class="text-xs font-semibold text-white">Need a hand?</p>
                        <p class="mt-1 max-w-[170px] text-[11px] leading-5 text-white/45">Our wealth team is here whenever you need us.</p>
                        <a href="#" class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#b9f7d2]">Visit help center <x-icon name="arrow-right" size="12" /></a>
                    </div>
                </div>
                <div class="mt-5 flex items-center gap-3 border-t border-white/10 pt-5">
                    <div class="grid h-9 w-9 place-items-center rounded-full bg-[#ffb6a5] text-xs font-bold text-[#0a1324]">{{ strtoupper(substr($currentUser?->name ?? 'JD', 0, 2)) }}</div>
                    <div class="min-w-0 flex-1"><p class="truncate text-xs font-semibold text-white">{{ $currentUser?->name ?? 'Jordan Davis' }}</p><p class="mt-0.5 truncate text-[10px] text-white/40">{{ $currentUser?->email ?? 'jordan@arcadia.co' }}</p></div>
                    <form action="{{ route('logout') }}" method="post"><button type="submit" class="text-white/35 hover:text-white" aria-label="Log out"><x-icon name="logout" size="16" /></button>@csrf</form>
                </div>
            </div>
        </aside>

        <div class="min-h-screen lg:pl-[272px]">
            <header class="flex h-20 items-center justify-between border-b border-[#dfe5ee] bg-white/80 px-5 backdrop-blur-xl sm:px-8 lg:px-12">
                <div class="flex items-center gap-4">
                    <button data-menu-button class="grid h-10 w-10 place-items-center rounded-xl border border-[#dfe5ee] text-[#66738a] lg:hidden" aria-label="Open navigation"><x-icon name="menu" size="18" /></button>
                    <div><p class="text-[11px] font-medium uppercase tracking-[.18em] text-[#9aa5b7]">{{ $admin ? 'Arcadia operations' : 'Monday, September 08, 2025' }}</p><h1 class="mt-1 text-lg font-semibold tracking-[-0.04em] text-[#0a1324]">{{ $title }}</h1></div>
                </div>
                <div class="flex items-center gap-3 sm:gap-5">
                    <button class="hidden h-10 w-52 items-center gap-2 rounded-xl border border-[#e1e6ee] px-3 text-left text-xs text-[#a2adbd] sm:flex"><x-icon name="search" size="16" /> <span>Search anything</span><span class="ml-auto rounded bg-[#f1f4f8] px-1.5 py-0.5 text-[10px] text-[#9aa5b7]">⌘ K</span></button>
                    <button class="relative grid h-10 w-10 place-items-center rounded-xl border border-[#e1e6ee] text-[#66738a]" aria-label="Notifications"><x-icon name="bell" size="17" /><span class="absolute right-2.5 top-2 h-1.5 w-1.5 rounded-full bg-[#ff836d] ring-2 ring-white"></span></button>
                    <div class="hidden h-7 w-px bg-[#e1e6ee] sm:block"></div>
                    <div class="grid h-10 w-10 place-items-center rounded-full bg-[#ffb6a5] text-xs font-bold text-[#0a1324]">{{ strtoupper(substr($currentUser?->name ?? 'JD', 0, 2)) }}</div>
                </div>
            </header>

            <main class="px-5 py-7 sm:px-8 lg:px-12 lg:py-10">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
