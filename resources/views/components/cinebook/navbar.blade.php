@php
    use Illuminate\Support\Facades\Route;

    // Fall back to "#" until each page exists, so the navbar never crashes.
    $url = fn (string $name, string $fallback = '#') => Route::has($name) ? route($name) : $fallback;
@endphp

<header class="border-b border-cb-line bg-cb-cream">
    <nav class="mx-auto flex min-h-[88px] max-w-[1440px] flex-wrap items-center gap-x-12 gap-y-3 px-4 py-5 md:px-8 xl:px-16">
        {{-- Brand --}}
        <a href="{{ $url('home', '/') }}" class="flex items-center gap-3">
            <span class="flex size-9 items-center justify-center rounded-xl bg-cb-brand text-[18px] font-bold text-white">▰</span>
            <span class="text-[26px] font-bold text-cb-ink">cinebook</span>
        </a>

        {{-- Links --}}
        <div class="flex flex-1 flex-wrap items-center gap-x-7 gap-y-2 text-[14px]">
            <a href="{{ $url('movies.index') }}" class="font-semibold text-cb-ink">Movies</a>
            <a href="{{ $url('bookings.index') }}" class="text-cb-muted hover:text-cb-ink">My bookings</a>
            <a href="{{ $url('home', '/') }}#cinema" class="text-cb-muted hover:text-cb-ink">CineBook Downtown</a>

            @auth
                <a href="{{ $url('dashboard') }}" class="rounded-full border border-cb-line bg-white px-[14px] py-[10px] font-semibold leading-[1.21] text-cb-ink">Dashboard</a>
            @else
                <a href="{{ $url('login') }}" class="rounded-full border border-cb-line bg-white px-[14px] py-[10px] font-semibold leading-[1.21] text-cb-ink">Sign in</a>
            @endauth
        </div>
    </nav>
</header>
