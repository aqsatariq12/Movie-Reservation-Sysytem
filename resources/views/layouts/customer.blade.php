@props(['title' => null])
@php
    $links = [];
    if (Route::has('movies.index')) {
        $links[] = ['Movies', route('movies.index')];
    }
    if (Route::has('bookings.index')) {
        $links[] = ['My bookings', route('bookings.index')];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-screen flex-col bg-[#080e1b] text-[#f4f7ff]">
        <header class="border-b border-[#283750] px-5 py-4 md:px-11 md:py-5">
            <div class="mx-auto flex max-w-[1360px] items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="text-[24px] md:text-[26px]"><span class="text-[#347bff]">▰</span> cinebook</a>

                {{-- Desktop menu --}}
                <nav class="hidden items-center gap-6 text-sm md:flex">
                    @foreach ($links as [$label, $url])
                        <a href="{{ $url }}" class="text-[#a0aec5] hover:text-white">{{ $label }}</a>
                    @endforeach
                    <span class="text-[#a0aec5]">CineBook Downtown</span>

                    @auth
                        <form method="POST" action="{{ route('logout') }}" class="flex items-center gap-3">
                            @csrf
                            <span class="text-[#a0aec5]">{{ auth()->user()->name }}</span>
                            <button type="submit" class="text-[#a0aec5] hover:text-white">Sign out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-[#a0aec5] hover:text-white">Sign in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="text-[#a0aec5] hover:text-white">Register</a>
                        @endif
                    @endauth
                </nav>

                {{-- Mobile menu --}}
                <details class="relative md:hidden">
                    <summary class="flex cursor-pointer list-none items-center gap-3 text-sm text-[#a0aec5]">
                        <span>Downtown</span>
                        <span class="text-xl leading-none" aria-label="Menu">☰</span>
                    </summary>
                    <div class="absolute right-0 z-20 mt-3 flex w-56 flex-col gap-3 rounded-lg border border-[#283750] bg-[#101a2d] p-4 text-sm">
                        @foreach ($links as [$label, $url])
                            <a href="{{ $url }}" class="text-[#a0aec5] hover:text-white">{{ $label }}</a>
                        @endforeach

                        @auth
                            <p class="text-[#a0aec5]">{{ auth()->user()->name }}</p>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-[#a0aec5] hover:text-white">Sign out</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="text-[#a0aec5] hover:text-white">Sign in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-[#a0aec5] hover:text-white">Register</a>
                            @endif
                        @endauth
                    </div>
                </details>
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-[1360px] flex-1 flex-col gap-6 px-5 py-6 md:px-11 md:py-8">
            {{ $slot }}
        </main>

        <footer class="border-t border-[#283750] px-5 py-6 text-xs text-[#a0aec5] md:px-11">
            <div class="mx-auto flex max-w-[1360px] flex-wrap justify-between gap-4">
                <p>CineBook Downtown · One cinema. Every great escape.</p>
                <p>USD · Stripe TEST MODE · No real money</p>
            </div>
        </footer>
    </body>
</html>