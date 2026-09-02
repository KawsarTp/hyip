<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sign in · Arcadia</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#0a1324] text-white antialiased">
        <div class="grid min-h-screen lg:grid-cols-[.95fr_1.05fr]">
            <section class="relative hidden overflow-hidden bg-[#101d32] p-12 lg:flex lg:flex-col lg:justify-between"><div class="noise absolute inset-0"></div><div class="relative"><a href="{{ route('home') }}" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-[14px] bg-[#b9f7d2] text-[#0a1324]"><x-icon name="spark" size="18" /></span><span class="text-[21px] font-semibold tracking-[-.06em]">arcadia<span class="text-[#b9f7d2]">.</span></span></a></div><div class="relative max-w-md"><p class="text-[10px] font-semibold uppercase tracking-[.2em] text-[#b9f7d2]">Your financial workspace</p><h1 class="display-copy mt-5 text-6xl font-semibold">Good to see you <span class="text-[#ff836d]">again.</span></h1><p class="mt-7 text-sm leading-7 text-white/45">Pick up where you left off with a clear view of your goals, progress, and next best move.</p><div class="mt-10 flex items-center gap-3 text-xs text-white/40"><span class="grid h-8 w-8 place-items-center rounded-full bg-white/10 text-[#b9f7d2]"><x-icon name="shield" size="15" /></span>Protected by bank-level encryption</div></div><p class="relative text-[11px] text-white/25">© 2025 Arcadia Financial</p></section>
            <section class="flex items-center justify-center bg-[#f6f8fb] px-5 py-10 text-[#0a1324] sm:px-10"><div class="w-full max-w-[420px]"><div class="mb-12 flex items-center justify-between lg:hidden"><a href="{{ route('home') }}" class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-[#0a1324] text-[#b9f7d2]"><x-icon name="spark" size="16" /></span><span class="text-xl font-semibold tracking-[-.06em]">arcadia<span class="text-[#7668f6]">.</span></span></a><a href="{{ route('register') }}" class="text-xs font-semibold text-[#536078]">Create account</a></div><div><p class="text-[10px] font-semibold uppercase tracking-[.2em] text-[#7668f6]">Welcome back</p><h2 class="display-copy mt-4 text-4xl font-semibold">Sign in to Arcadia.</h2><p class="mt-4 text-sm text-[#7b879a]">Your next chapter is waiting.</p></div>
                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-[#f1d9c9] bg-[#fffaf6] px-4 py-3 text-xs leading-5 text-[#a2503f]" role="alert">{{ $errors->first() }}</div>
                @endif
                @if (session('status'))
                    <div class="mt-6 rounded-xl border border-[#cdebd8] bg-[#eaf9f0] px-4 py-3 text-xs leading-5 text-[#26794f]">{{ session('status') }}</div>
                @endif
                <form class="mt-8 grid gap-5" action="{{ route('login.store') }}" method="post">
                    @csrf
                    <div><label for="email" class="mb-2 block text-xs font-semibold text-[#536078]">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus placeholder="you@example.com" class="h-12 w-full rounded-xl border border-[#dfe5ec] bg-white px-4 text-sm outline-none transition placeholder:text-[#b2bbc8] focus:border-[#7668f6] focus:ring-4 focus:ring-[#7668f6]/10"></div>
                    <div><div class="mb-2 flex items-center justify-between"><label for="password" class="block text-xs font-semibold text-[#536078]">Password</label><a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-[#7668f6]">Forgot password?</a></div><input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password" class="h-12 w-full rounded-xl border border-[#dfe5ec] bg-white px-4 text-sm outline-none transition placeholder:text-[#b2bbc8] focus:border-[#7668f6] focus:ring-4 focus:ring-[#7668f6]/10"></div>
                    <label class="flex items-center gap-2 text-xs text-[#7b879a]"><input name="remember" value="1" type="checkbox" class="h-4 w-4 rounded border-[#ccd4df] text-[#7668f6] focus:ring-[#7668f6]"> Remember this device</label>
                    <button type="submit" class="mt-2 flex h-12 items-center justify-center gap-2 rounded-xl bg-[#0a1324] text-sm font-semibold text-white shadow-xl shadow-[#0a1324]/15 transition hover:-translate-y-0.5">Sign in <span class="text-[#b9f7d2]">↗</span></button>
                </form>
                <p class="mt-8 text-center text-xs text-[#7b879a]">New to Arcadia? <a href="{{ route('register') }}" class="font-semibold text-[#0a1324]">Create an account</a></p><div class="mt-12 flex items-center justify-center gap-2 text-[10px] text-[#a1abba]"><x-icon name="lock" size="13" /> Secure 256-bit encrypted connection</div>
            </div></section>
        </div>
    </body>
</html>
