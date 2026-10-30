@extends('layouts.admin')

@section('content')
    <div class="flex flex-col gap-6">

        {{-- Page heading --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#7f9ac2]">
                    Overview
                </p>

                <h1 class="mt-2 text-3xl font-semibold text-white">
                    Good day, {{ auth()->user()->name }}
                </h1>

                <p class="mt-2 text-sm text-[#91a3bf]">
                    Here's what's happening at your cinema today.
                </p>
            </div>

            <a href="{{ Route::has('admin.reports.index') ? route('admin.reports.index') : '#' }}"
                class="rounded-lg bg-[#347bff] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#2468e8]">
                View sales report
            </a>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">

            <div class="rounded-xl border border-[#283750] bg-[#101c30] p-5">
                <p class="text-sm text-[#91a3bf]">Confirmed bookings today</p>

                <p class="mt-4 text-3xl font-semibold text-white">
                    {{ number_format($confirmedToday) }}
                </p>

                <p class="mt-2 text-xs text-[#7186a7]">
                    {{ number_format($bookingsCount) }} total bookings
                </p>
            </div>

            <div class="rounded-xl border border-[#283750] bg-[#101c30] p-5">
                <p class="text-sm text-[#91a3bf]">Payments received today</p>

                <p class="mt-4 text-3xl font-semibold text-white">
                    {{ \App\Models\Showtime::money($revenueToday) }}
                </p>

                <p class="mt-2 text-xs text-[#7186a7]">
                    Successful payments received today
                </p>
            </div>

            <div class="rounded-xl border border-[#283750] bg-[#101c30] p-5">
                <p class="text-sm text-[#91a3bf]">Refunds issued today</p>

                <p class="mt-4 text-3xl font-semibold text-white">
                    {{ \App\Models\Showtime::money($refundsToday) }}
                </p>

                <p class="mt-2 text-xs text-[#7186a7]">
                    Successfully completed refunds
                </p>
            </div>

            <div class="rounded-xl border border-[#283750] bg-[#101c30] p-5">
                <p class="text-sm text-[#91a3bf]">Net cash movement today</p>

                <p class="mt-4 text-3xl font-semibold {{ $netCashMovementToday < 0 ? 'text-red-400' : 'text-white' }}">
                    {{ \App\Models\Showtime::money($netCashMovementToday) }}
                </p>

                <p class="mt-2 text-xs text-[#7186a7]">
                    Payments received minus refunds
                </p>
            </div>

            <div class="rounded-xl border border-[#283750] bg-[#101c30] p-5">
                <p class="text-sm text-[#91a3bf]">Next 24h occupancy</p>

                <p class="mt-4 text-3xl font-semibold text-white">
                    {{ $occupancy }}%
                </p>

                <p class="mt-2 text-xs text-[#7186a7]">
                    {{ $soldSeats }} of {{ $totalSeats }} seats sold
                </p>
            </div>

            <div class="rounded-xl border border-[#283750] bg-[#101c30] p-5">
                <p class="text-sm text-[#91a3bf]">Upcoming showtimes</p>

                <p class="mt-4 text-3xl font-semibold text-white">
                    {{ number_format($upcomingShowtimesCount) }}
                </p>

                <p class="mt-2 text-xs text-[#7186a7]">
                    Scheduled screenings in the next 24 hours
                </p>
            </div>

        </div>

        {{-- Sales chart and attention panel --}}
        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

            {{-- Hourly sales --}}
            <section class="rounded-xl border border-[#283750] bg-[#101c30] p-5 xl:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-white">
                            Ticket sales today
                        </h2>

                        <p class="mt-1 text-xs text-[#7186a7]">
                            Confirmed bookings by hour
                        </p>
                    </div>

                    <span class="rounded-full border border-[#283750] px-3 py-1 text-xs text-[#91a3bf]">
                        Today
                    </span>
                </div>

                <div class="mt-7 flex h-52 items-end gap-2 overflow-x-auto">
                    @foreach ($salesChart as $point)
                        <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                            <span class="text-[10px] text-[#91a3bf]">
                                {{ $point['sales'] ?: '' }}
                            </span>

                            <div class="flex h-36 w-full items-end rounded-t-md bg-[#172945]">
                                <div class="w-full rounded-t-md bg-[#347bff]"
                                    style="height: {{ $salesChart->max('sales') > 0 ? max(5, ($point['sales'] / $salesChart->max('sales')) * 100) : 5 }}%">
                                </div>
                            </div>

                            <span class="text-[10px] text-[#7186a7]">
                                {{ $point['hour'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Needs attention --}}
            <section class="rounded-xl border border-[#283750] bg-[#101c30] p-5">
                <h2 class="text-lg font-semibold text-white">
                    Needs attention
                </h2>

                <p class="mt-1 text-xs text-[#7186a7]">
                    Items that may need your action
                </p>

                <div class="mt-5 flex flex-col gap-4">

                    @foreach ([
                            ['Failed refunds', $failedRefunds],
                            ['Stuck payments', $stuckPayments],
                            ['Failed webhooks', $failedWebhooks],
                            ['Failed jobs', $failedJobs],
                        ] as [$label, $count])

                        <div class="flex items-center justify-between gap-3 border-b border-[#283750] pb-3 last:border-0">
                            <span class="text-sm text-[#b8c6dc]">
                                {{ $label }}
                            </span>

                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold
                                                {{ $count > 0 ? 'bg-red-500/15 text-red-400' : 'bg-emerald-500/10 text-emerald-400' }}">
                                {{ $count }}
                            </span>
                        </div>

                    @endforeach

                </div>
            </section>

        </div>

        {{-- Upcoming showtimes --}}
        <section class="overflow-hidden rounded-xl border border-[#283750] bg-[#101c30]">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#283750] px-5 py-5">
                <div>
                    <h2 class="text-lg font-semibold text-white">
                        Upcoming showtimes
                    </h2>

                    <p class="mt-1 text-xs text-[#7186a7]">
                        Scheduled screenings in the next 24 hours
                    </p>
                </div>

                <a href="{{ route('admin.showtimes.index') }}" class="text-sm font-medium text-[#70a5ff] hover:text-white">
                    Manage showtimes →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[650px] text-left text-sm">
                    <thead class="bg-[#0c1729] text-xs uppercase tracking-wider text-[#7186a7]">
                        <tr>
                            <th class="px-5 py-4 font-medium">Movie</th>
                            <th class="px-5 py-4 font-medium">Screen</th>
                            <th class="px-5 py-4 font-medium">Starts at</th>
                            <th class="px-5 py-4 font-medium">Occupancy</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#283750]">
                        @forelse ($upcomingShowtimes as $showtime)
                            <tr class="transition hover:bg-[#172945]">
                                <td class="px-5 py-4 font-medium text-white">
                                    {{ $showtime->movie?->title ?? 'Movie unavailable' }}
                                </td>

                                <td class="px-5 py-4 text-[#b8c6dc]">
                                    {{ $showtime->screen?->name ?? 'Screen unavailable' }}
                                </td>

                                <td class="px-5 py-4 text-[#b8c6dc]">
                                    {{ $showtime->starts_at->format('M d, h:i A') }}
                                </td>

                                <td class="px-5 py-4">
                                    @php
                                        $percentage = $showtime->total_seats > 0
                                            ? round(($showtime->sold_seats / $showtime->total_seats) * 100)
                                            : 0;
                                    @endphp

                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-24 overflow-hidden rounded-full bg-[#283750]">
                                            <div class="h-full rounded-full bg-[#347bff]" style="width: {{ $percentage }}%">
                                            </div>
                                        </div>

                                        <span class="text-xs text-[#b8c6dc]">
                                            {{ $percentage }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-[#7186a7]">
                                    No scheduled showtimes in the next 24 hours.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Existing cinema statistics --}}
        <section class="rounded-xl border border-[#283750] bg-[#101c30] p-5">
            <h2 class="text-sm font-semibold text-white">
                Cinema overview
            </h2>

            <div class="mt-4 grid grid-cols-2 gap-5 sm:grid-cols-4">
                @foreach ([
                        ['Movies', $moviesCount],
                        ['Screens', $screensCount],
                        ['Showtimes', $showtimesCount],
                        ['Bookings', $bookingsCount],
                    ] as [$label, $count])

                    <div>
                        <p class="text-xs text-[#7186a7]">{{ $label }}</p>
                        <p class="mt-2 text-xl font-semibold text-white">
                            {{ number_format($count) }}
                        </p>
                    </div>

                @endforeach
            </div>
        </section>

        <p class="text-xs leading-5 text-[#7186a7]">
            Occupancy is calculated using sold seats divided by configured seats
            for scheduled showtimes starting in the next 24 hours. Held seats are
            not counted as sold.
        </p>

    </div>
@endsection