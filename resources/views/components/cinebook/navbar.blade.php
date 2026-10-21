@php
    use Illuminate\Support\Facades\Route;
    use App\Models\Hold;

    // Prevent errors when a route has not been created yet.
    $url = fn(string $name, string $fallback = '#') =>
        Route::has($name) ? route($name) : $fallback;

    $settingsUrl = $url(
        'profile.edit',
        $url('settings.profile', '/settings/profile')
    );

    // Find this customer's active, unexpired hold.
    $activeHold = auth()->check()
        ? Hold::where('user_id', auth()->id())
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first()
        : null;
@endphp

<header class="border-b border-cb-line bg-cb-cream">
    <nav
        class="mx-auto flex min-h-[88px] max-w-[1440px] flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-4 md:gap-x-12 md:px-8 md:py-5 xl:px-16">

        {{-- Brand --}}
        <a href="{{ $url('home', '/') }}" class="flex items-center gap-3">
            <span
                class="flex size-9 items-center justify-center rounded-xl bg-cb-brand text-[18px] font-bold text-white">
                ▰
            </span>
            <span class="text-[26px] font-bold text-cb-ink">cinebook</span>
        </a>

        {{-- Navigation links --}}
        <div class="flex w-full flex-wrap items-center gap-x-5 gap-y-3 text-[14px] sm:gap-x-7 md:w-auto md:flex-1">

            <a href="{{ $url('movies.index') }}" class="font-semibold text-cb-ink">
                Movies
            </a>

            <a href="{{ $url('bookings.index') }}" class="text-cb-muted hover:text-cb-ink">
                My bookings
            </a>

            @if ($activeHold && Route::has('holds.show'))
                <a href="{{ route('holds.show', $activeHold) }}"
                    class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-2 font-semibold text-blue-700 transition hover:bg-blue-100">
                    <span class="size-2 rounded-full bg-blue-600"></span>
                    Held seats
                </a>
            @endif

            <a href="{{ $url('home', '/') }}#cinema" class="text-cb-muted hover:text-cb-ink">
                CineBook Downtown
            </a>

            @auth
                {{-- Logged-in user dropdown --}}
                <details class="group relative ml-auto">
                    <summary
                        class="flex cursor-pointer list-none items-center gap-2 rounded-full border border-cb-line bg-white px-4 py-[10px] font-semibold text-cb-ink transition hover:bg-[#f7f4ee] [&::-webkit-details-marker]:hidden">

                        <span class="max-w-[150px] truncate">
                            {{ auth()->user()->name }}
                        </span>

                        <svg class="size-4 shrink-0 transition-transform group-open:rotate-180" viewBox="0 0 20 20"
                            fill="none" aria-hidden="true">
                            <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </summary>

                    {{-- Dropdown options --}}
                    <div
                        class="absolute right-0 top-full z-50 mt-3 w-56 overflow-hidden rounded-2xl border border-cb-line bg-white p-2 shadow-lg">

                        {{-- User information --}}
                        <div class="border-b border-cb-line px-3 py-3">
                            <p class="truncate text-sm font-semibold text-cb-ink">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="mt-1 truncate text-xs text-cb-muted">
                                {{ auth()->user()->email }}
                            </p>
                        </div>

                        {{-- Settings --}}
                        <a href="{{ $settingsUrl }}"
                            class="mt-2 flex items-center rounded-xl px-3 py-2.5 text-sm text-cb-ink transition hover:bg-[#f7f4ee]">
                            Settings
                        </a>


                        {{-- Log out --}}
                        <div class="mt-1 border-t border-cb-line pt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <button type="submit"
                                    class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-medium text-[#b42335] transition hover:bg-[#fff1f2]">
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
                </details>
            @else
                {{-- Logged-out user --}}
                <a href="{{ $url('login') }}"
                    class="ml-auto rounded-full border border-cb-line bg-white px-[14px] py-[10px] font-semibold leading-[1.21] text-cb-ink transition hover:bg-[#f7f4ee]">
                    Sign in
                </a>
            @endauth

        </div>
    </nav>
</header>