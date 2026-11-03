<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quote Calculator') · {{ config('quotecalc.company.name') }}</title>
    {{-- Tailwind Play CDN keeps the demo free of an npm build step. Use a compiled build in production. --}}
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('calculator') }}" class="text-lg font-semibold tracking-tight">
                {{ config('quotecalc.company.name') }} <span class="font-normal text-slate-400">· Instant quote</span>
            </a>
            <span class="text-sm text-slate-500">Prices in {{ config('quotecalc.currency') }}</span>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @yield('content')
    </main>

    <footer class="mx-auto max-w-6xl px-4 pb-10 text-xs text-slate-400">
        Demo project by Aleksandr Oleinik · built with Laravel · prices are calculated on the server.
    </footer>
</body>
</html>
