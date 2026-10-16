{{--
    Split layout used by every account screen (login, register, verify email,
    forgot / reset password). Figma: "05 · Customer — Login".

    Usage: wrap the page in the x-layouts.cinebook-auth component and pass
    title, heading and description. Everything inside becomes blocks under the
    heading, spaced 28px apart (see pages/auth/login.blade.php).
--}}
@props([
    'title' => 'Account',
    'heading',
    'description' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | CineBook Downtown</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cb-cream font-cinebook text-cb-ink antialiased">
    <div class="flex min-h-screen">

        {{-- Brand panel (desktop only) --}}
        <aside class="relative isolate hidden overflow-hidden p-14 lg:flex lg:w-[46.5%] lg:shrink-0 lg:flex-col lg:gap-8">
            <img src="{{ asset('images/cinebook/movie-1.png') }}" alt="" class="absolute inset-0 -z-20 size-full object-cover">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgba(247,244,238,0.96),rgba(247,244,238,0.53)_55%,rgba(247,244,238,0.06))]"></div>

            <a href="{{ route('home') }}" class="text-[26px] leading-[normal] text-cb-ink">▰ cinebook</a>

            <div class="mt-auto pb-28 text-[14px] leading-[1.6] text-cb-muted">
                <p>YOUR NEXT GREAT ESCAPE</p>
                <p class="mt-[22px] text-[54px] font-bold leading-[1.2] text-cb-ink">The big screen. Your kind of story.</p>
                <p class="mt-[22px]">One cinema. Extraordinary worlds. Make your next night a movie night.</p>
            </div>
        </aside>

        {{-- Form column --}}
        <main class="flex min-w-0 flex-1 flex-col gap-7 px-4 py-10 md:px-12 xl:px-24 xl:py-16">
            {{-- On small screens the brand panel is hidden, so show the logo here --}}
            <a href="{{ route('home') }}" class="text-[26px] leading-[normal] text-cb-ink lg:hidden">▰ cinebook</a>

            <div class="text-[14px] leading-[1.6] text-cb-muted">
                <a href="{{ route('movies.index') }}" class="hover:text-cb-ink">← Back to movies</a>

                <h1 class="mt-[22px] text-[36px] font-bold leading-[1.2] text-cb-ink">{{ $heading }}</h1>

                @if ($description)
                    <p class="mt-[22px]">{{ $description }}</p>
                @endif
            </div>

            {{ $slot }}
        </main>
    </div>
</body>
</html>
