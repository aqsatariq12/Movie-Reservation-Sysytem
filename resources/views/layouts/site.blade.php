<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'CineBook' }} · CineBook</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#080d19] text-white antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

    {{-- Top navigation --}}
    <header class="border-b border-[#1a2338]">
        <div class="mx-auto flex h-20 max-w-[1312px] items-center justify-between px-6 lg:px-0">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-2xl font-light tracking-tight">
                <span class="h-1.5 w-6 rounded-sm bg-[#2f6bff]"></span>
                cinebook
            </a>

            <nav class="flex items-center gap-6 text-sm text-[#b4bccb]">
                <a href="{{ route('home') }}" class="text-[#7fb0ff]">Movies</a>
                <a href="{{ route('dashboard') }}" class="hover:text-white">My bookings</a>
                <span class="hidden md:inline">CineBook Downtown</span>
                <span class="hidden sm:inline">{{ auth()->user()->name ?? 'Guest' }} ▾</span>
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="mt-16 border-t border-[#1a2338]">
        <div class="mx-auto flex max-w-[1312px] flex-col gap-2 px-6 py-8 text-xs text-[#6f7a90] sm:flex-row sm:gap-24 lg:px-0">
            <span>CineBook Downtown · One cinema. Every great escape.</span>
            <span>USD · Stripe TEST MODE · No real money</span>
        </div>
    </footer>
</body>
</html>