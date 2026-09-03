@props([
    'admin' => false,
    'active' => 'overview',
    'title' => 'Overview',
])

@php
    $currentUser = auth()->user();
    $localization = app(\App\Services\LocalizationService::class);
    $availableLanguages = $localization->enabledLanguages();
    $availableCurrencies = $localization->enabledCurrencies();
    $currentCurrencyCode = $localization->currentCurrencyCode();
    $currentLanguage = $localization->language(app()->getLocale());
    $siteSettings = \App\Models\SiteSetting::query()->pluck('value', 'key');
    $siteName = $siteSettings->get('site_name', 'Arcadia');
    $logoUrl = $siteSettings->get('logo_path') ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->get('logo_path')) : null;
    $faviconUrl = $siteSettings->get('favicon_path') ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->get('favicon_path')) : null;

    $navGroups = $admin
        ? [
            [
                'label' => 'Workspace',
                'items' => [
                    ['key' => 'overview', 'label' => 'Overview', 'icon' => 'grid', 'href' => route('admin.dashboard')],
                ],
            ],
            [
                'label' => 'Management',
                'items' => [
                    ['key' => 'users', 'label' => 'Users & staff', 'icon' => 'users', 'href' => route('admin.users.index')],
                    ['key' => 'investments', 'label' => 'Investments', 'icon' => 'briefcase', 'href' => route('admin.investments.index')],
                    ['key' => 'plans', 'label' => 'Investment plans', 'icon' => 'spark', 'href' => route('admin.investment-plans.index')],
                    ['key' => 'referrals', 'label' => 'Referrals', 'icon' => 'users', 'href' => route('admin.referrals.index')],
                    ['key' => 'support', 'label' => 'Support tickets', 'icon' => 'help', 'href' => route('admin.support.index')],
                ],
            ],
            [
                'label' => 'Finance',
                'items' => [
                    ['key' => 'deposits', 'label' => 'Deposits', 'icon' => 'arrow-down', 'href' => route('admin.deposits.index')],
                    ['key' => 'payouts', 'label' => 'Payouts', 'icon' => 'arrow-up', 'href' => route('admin.withdrawals.index')],
                    ['key' => 'ledger', 'label' => 'Ledger', 'icon' => 'receipt', 'href' => route('admin.ledger.index')],
                    ['key' => 'fees', 'label' => 'Fees & taxes', 'icon' => 'receipt', 'href' => route('admin.fees.index')],
                    ['key' => 'payment-gateways', 'label' => 'Payment methods', 'icon' => 'settings', 'href' => route('admin.payment-gateways.index')],
                    ['key' => 'withdrawal-methods', 'label' => 'Payout methods', 'icon' => 'arrow-up', 'href' => route('admin.withdrawal-methods.index')],
                ],
            ],
            [
                'label' => 'Risk & compliance',
                'items' => [
                    ['key' => 'compliance', 'label' => 'Compliance', 'icon' => 'shield', 'href' => route('admin.kyc.index'), 'badge' => '3'],
                    ['key' => 'audit', 'label' => 'Audit logs', 'icon' => 'shield', 'href' => route('admin.audit.index')],
                ],
            ],
            [
                'label' => 'System',
                'items' => [
                    ['key' => 'cms', 'label' => 'CMS pages', 'icon' => 'file', 'href' => route('admin.cms.index')],
                    ['key' => 'email-templates', 'label' => 'Email templates', 'icon' => 'file', 'href' => route('admin.email-templates.index')],
                    ['key' => 'roles', 'label' => 'Roles & permissions', 'icon' => 'shield', 'href' => route('admin.roles.index')],
                    ['key' => 'reports', 'label' => 'Reports', 'icon' => 'receipt', 'href' => '#'],
                    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'href' => route('admin.settings.index')],
                    ['key' => 'languages', 'label' => 'Languages', 'icon' => 'globe', 'href' => route('admin.languages.index')],
                    ['key' => 'currencies', 'label' => 'Currencies', 'icon' => 'wallet', 'href' => route('admin.currencies.index')],
                ],
            ],
        ]
        : [
            [
                'label' => 'Overview',
                'items' => [
                    ['key' => 'overview', 'label' => 'Overview', 'icon' => 'grid', 'href' => route('dashboard')],
                ],
            ],
            [
                'label' => 'Portfolio',
                'items' => [
                    ['key' => 'investments', 'label' => 'My investments', 'icon' => 'briefcase', 'href' => route('investments.index')],
                    ['key' => 'wallet', 'label' => 'Wallet', 'icon' => 'wallet', 'href' => route('wallet.index')],
                ],
            ],
            [
                'label' => 'Money movement',
                'items' => [
                    ['key' => 'deposits', 'label' => 'Deposit funds', 'icon' => 'arrow-down', 'href' => route('deposits.create')],
                    ['key' => 'withdrawals', 'label' => 'Withdraw funds', 'icon' => 'arrow-up', 'href' => route('withdrawals.create')],
                    ['key' => 'activity', 'label' => 'Activity', 'icon' => 'activity', 'href' => '#'],
                ],
            ],
            [
                'label' => 'Account',
                'items' => [
                    ['key' => 'referrals', 'label' => 'Referrals', 'icon' => 'users', 'href' => route('referrals.index')],
                    ['key' => 'verification', 'label' => 'Verification', 'icon' => 'shield', 'href' => route('kyc.edit')],
                    ['key' => 'support', 'label' => 'Help & support', 'icon' => 'help', 'href' => route('support.tickets.index')],
                    ['key' => 'statements', 'label' => 'Statements', 'icon' => 'file', 'href' => '#'],
                    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'href' => '#'],
                ],
            ],
        ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $currentLanguage?->direction ?? 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a1324">
        @if ($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif
        <title>{{ $title }} · {{ $siteName }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f5f7fb] antialiased">
        <div data-sidebar-backdrop class="pointer-events-none fixed inset-0 z-30 bg-[#07101f]/50 opacity-0 transition-opacity lg:hidden"></div>

        <aside data-sidebar class="fixed inset-y-0 left-0 z-40 flex w-[272px] -translate-x-full flex-col bg-[#0a1324] text-white transition-transform duration-300 lg:translate-x-0">
            <div class="flex h-24 items-center gap-3 px-7">
                <a href="{{ $admin ? route('admin.dashboard') : route('dashboard') }}" class="flex items-center gap-3" aria-label="Arcadia home">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="max-h-10 max-w-[150px] object-contain">
                    @else
                        <span class="grid h-10 w-10 place-items-center rounded-[14px] bg-[#b9f7d2] text-[#0a1324] shadow-[0_8px_25px_rgba(185,247,210,.2)]">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"><path d="M4 17.5 10.5 4l3.2 7h6.3L16 17.5H4Z"/><path d="m13.7 11 2.7 6.5"/></svg>
                        </span>
                        <span class="text-[21px] font-semibold tracking-[-0.06em]">{{ strtolower($siteName) }}<span class="text-[#b9f7d2]">.</span></span>
                    @endif
                </a>
            </div>

            <nav class="sidebar-scroll flex-1 overflow-y-auto px-4" aria-label="Primary navigation">
                @foreach ($navGroups as $group)
                    <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-[.18em] text-white/30 {{ $loop->first ? 'pt-4' : 'pt-7' }}">{{ $localization->text('navigation.'.str($group['label'])->slug(), $group['label']) }}</p>
                    <div class="grid gap-1">
                        @foreach ($group['items'] as $item)
                            <a href="{{ $item['href'] }}" class="group flex items-center justify-between rounded-[14px] px-3 py-2.5 text-[13px] font-medium transition {{ $active === $item['key'] ? 'bg-white text-[#0a1324] shadow-lg shadow-black/10' : 'text-white/55 hover:bg-white/[.07] hover:text-white' }}">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-[10px] transition {{ $active === $item['key'] ? 'bg-[#eef3f8]' : 'bg-white/[.03] group-hover:bg-white/[.08]' }}"><x-icon name="{{ $item['icon'] }}" size="16" /></span>
                                    <span class="truncate">{{ $localization->text('navigation.'.str($item['label'])->slug(), $item['label']) }}</span>
                                </span>
                                @if (isset($item['badge']))
                                    <span class="rounded-md {{ $active === $item['key'] ? 'bg-[#e9eff7] text-[#536078]' : 'bg-white/10 text-white/45' }} px-1.5 py-0.5 text-[10px] font-semibold">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="p-5">
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
                    <div class="hidden items-center gap-2 md:flex">
                        @if ($availableLanguages->isNotEmpty())
                            <form action="{{ route('preferences.locale.update') }}" method="post">
                                @csrf @method('patch')
                                <label for="header-locale" class="sr-only">Language</label>
                                <select id="header-locale" name="locale" onchange="this.form.submit()" class="h-10 max-w-[116px] rounded-xl border border-[#e1e6ee] bg-white px-2.5 text-[11px] font-semibold text-[#536078] outline-none focus:border-[#7668f6]">
                                    @foreach ($availableLanguages as $language)
                                        <option value="{{ $language->code }}" @selected($language->code === app()->getLocale())>{{ $language->native_name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                        @if ($availableCurrencies->isNotEmpty())
                            <form action="{{ route('preferences.currency.update') }}" method="post">
                                @csrf @method('patch')
                                <label for="header-currency" class="sr-only">Currency</label>
                                <select id="header-currency" name="currency_code" onchange="this.form.submit()" class="h-10 max-w-[92px] rounded-xl border border-[#e1e6ee] bg-white px-2.5 text-[11px] font-semibold text-[#536078] outline-none focus:border-[#7668f6]">
                                    @foreach ($availableCurrencies as $currency)
                                        <option value="{{ $currency->code }}" @selected($currency->code === $currentCurrencyCode)>{{ $currency->code }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </div>
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
