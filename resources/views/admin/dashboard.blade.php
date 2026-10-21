@extends('layouts.admin')

@section('content')
    <div class="flex flex-col gap-2">
        <p class="text-sm text-[#a0aec5]">Overview</p>

        <h1 class="text-3xl font-semibold text-white">
            Admin Dashboard
        </h1>

        <p class="text-sm text-[#a0aec5]">
            Manage your cinema, movie schedule, and reservations.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Movies', $moviesCount, 'Total movies in CineBook'],
            ['Screens', $screensCount, 'Cinema screens configured'],
            ['Showtimes', $showtimesCount, 'Scheduled and past showtimes'],
            ['Bookings', $bookingsCount, 'Customer reservations'],
        ] as [$label, $count, $description])
            <div class="rounded-xl border border-[#283750] bg-[#0c1424] p-5">
                <p class="text-sm text-[#a0aec5]">{{ $label }}</p>

                <p class="mt-3 text-3xl font-semibold text-white">
                    {{ $count }}
                </p>

                <p class="mt-2 text-xs text-[#a0aec5]">
                    {{ $description }}
                </p>
            </div>
        @endforeach
    </div>

    <section class="rounded-xl border border-[#283750] bg-[#0c1424] p-6">
        <h2 class="text-lg font-semibold text-white">Quick Actions</h2>

        <div class="mt-5 flex flex-wrap gap-3">
            <a href="{{ route('admin.screens.index') }}"
               class="rounded-lg bg-[#347bff] px-4 py-2.5 text-sm font-medium text-white">
                Manage Screens
            </a>

            <a href="{{ route('admin.showtimes.index') }}"
               class="rounded-lg border border-[#283750] px-4 py-2.5 text-sm font-medium text-white">
                Manage Showtimes
            </a>

            <a href="{{ route('home') }}"
               class="rounded-lg border border-[#283750] px-4 py-2.5 text-sm font-medium text-white">
                View Customer Website
            </a>
        </div>
    </section>
@endsection