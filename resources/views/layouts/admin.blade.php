@props(['title' => null])
@php
    $navGroups = [
        'Overview' => [
            ['Dashboard', 'admin.dashboard', 'admin.dashboard'],
        ],
        'Programming' => [
            ['Movies', 'admin.movies.index', 'admin.movies.*'],
            ['Genres', 'admin.genres.index', 'admin.genres.*'],
            ['Screens', 'admin.screens.index', 'admin.screens.*'],
            ['Showtimes', 'admin.showtimes.index', 'admin.showtimes.*'],
        ],
        'Reservations' => [
            ['Bookings', 'admin.bookings.index', 'admin.bookings.*'],
            ['Payments', 'admin.payments.index', 'admin.payments.*'],
            ['Refunds', 'admin.refunds.index', 'admin.refunds.*'],
        ],
        'People' => [
            ['Customers', 'admin.customers.index', 'admin.customers.*'],
            ['Staff & admins', 'admin.staff.index', 'admin.staff.*'],
        ],
        'Operations' => [
            ['Reports', 'admin.reports.index', 'admin.reports.*'],
            ['Audit log', 'admin.audit.index', 'admin.audit.*'],
            ['Settings', 'admin.settings.edit', 'admin.settings.*'],
            ['Failed jobs', 'admin.failed-jobs.index', 'admin.failed-jobs.*'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-[#080e1b] text-[#f4f7ff]">
    <div class="flex min-h-screen">

        <aside class="flex w-56 shrink-0 flex-col gap-6 border-r border-[#283750] bg-[#0c1424] px-5 py-8">
            <p class="text-[22px]"><span class="text-[#347bff]">▰</span> cinebook</p>

            <nav class="flex flex-col gap-4 text-[13px]">
                @foreach ($navGroups as $group => $items)
                    <div class="flex flex-col">
                        <p class="mb-1 text-xs uppercase tracking-wide text-[#a0aec5]">{{ $group }}</p>
                        @foreach ($items as [$label, $routeName, $pattern])
                            @if (Route::has($routeName))
                                <a href="{{ route($routeName) }}"
                                    class="py-1.5 pl-3 {{ request()->routeIs($pattern) ? 'font-semibold text-white' : 'text-[#a0aec5] hover:text-white' }}">
                                    {{ request()->routeIs($pattern) ? '▸ ' : '' }}{{ $label }}
                                </a>
                            @else
                                <span class="cursor-not-allowed py-1.5 pl-3 text-[#a0aec5]/50">{{ $label }}</span>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="mt-auto flex flex-col gap-1 text-[13px] text-[#a0aec5]">
                <p>
                    {{ auth()->user()->name }} ·
                    {{ ucfirst(auth()->user()->role?->value ?? 'user') }}
                </p>
                <p>Downtown · TEST MODE</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-left hover:text-white">Sign out</button>
                </form>
            </div>
        </aside>

        <main class="flex min-w-0 flex-1 flex-col gap-6 p-9">
             @if (session('status'))
                <div class="rounded-lg border border-[#347bff]/30 bg-[#347bff]/10 bg-[#347bff]/10 px-4 py-3 text-sm text-[#f4f7ff]">
                    {{ session('status') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>

</html>