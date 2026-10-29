<x-layouts.cinebook :title="__('Hold expired')">
    @php
        $showtime = $booking->showtime;
        $movie = $showtime?->movie;
        $screen = $showtime?->screen;

        $seatNames = \Illuminate\Support\Facades\DB::table('booking_seats')
            ->where('booking_id', $booking->id)
            ->join('seats', 'booking_seats.seat_id', '=', 'seats.id')
            ->pluck('seats.seat_number')
            ->implode(', ');

        $hold = $booking->hold;
    @endphp

    <div class="mx-auto max-w-[1312px] px-6 py-10 lg:px-0">
        <div class="rounded-2xl border border-[#E7DED2] bg-white p-8 text-[#252525] shadow-sm sm:p-12">

            <h2 class="text-3xl font-bold">Hold expired</h2>

            <div class="mt-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-amber-500 bg-amber-50 text-amber-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
            </div>

            <p class="mt-6 text-xs font-semibold uppercase tracking-wide text-amber-700">
                ! Expired · Seats released
            </p>

            <h1 class="mt-6 text-4xl font-bold leading-tight sm:text-5xl">
                Your time ran out.
            </h1>

            <p class="mt-6 text-sm leading-relaxed text-[#6B6259]">
                Your booking hold has expired. Seat availability depends on the current booking and hold status.
            </p>

            <div class="mt-6 space-y-5 text-sm text-[#6B6259]">
                <p>
                    <span class="font-semibold text-[#252525]">Booking ·</span>
                    {{ $booking->booking_reference }}
                </p>

                <p>
                    <span class="font-semibold text-[#252525]">Movie ·</span>
                    {{ $movie?->title ?? 'Movie details unavailable' }}

                    @if ($screen?->name)
                        · {{ $screen->name }}
                    @endif

                    @if ($showtime?->starts_at)
                        · {{ \Illuminate\Support\Carbon::parse($showtime->starts_at)->format('M d · H:i') }}
                    @endif
                </p>

                <p>
                    <span class="font-semibold text-[#252525]">Seats ·</span>
                    {{ $seatNames !== '' ? $seatNames : 'Not available' }}
                </p>

                @if ($hold?->held_until)
                    <p>
                        <span class="font-semibold text-[#252525]">Hold expired at ·</span>
                        {{ \Illuminate\Support\Carbon::parse($hold->held_until)->format('M d, Y · H:i:s') }}
                    </p>
                @endif
            </div>

            <div class="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm leading-relaxed text-blue-900">
                <p class="font-semibold">ⓘ No ticket was issued</p>
                <p class="mt-1">
                    Choose from the seats currently available. Check the booking status before starting a new reservation.
                </p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center justify-center rounded-lg bg-[#2F6BFF] px-5 py-3 text-sm font-medium text-white transition hover:bg-[#2459d6]">
                    Find available seats
                </a>

                <a href="{{ route('bookings.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-[#E7DED2] bg-white px-5 py-3 text-sm font-medium text-[#252525] transition hover:bg-[#F8F5F0]">
                    My bookings
                </a>
            </div>
        </div>
    </div>
</x-layouts.cinebook>
