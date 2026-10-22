<x-layouts::site :title="__('Payment failed')">
    @php
        $showtime = $booking->showtime;
        $movie = $showtime?->movie;
        $screen = $showtime?->screen;

        $amountCents = $payment->amount_cents
            ?? $booking->total_amount_cents
            ?? 0;

        $currency = strtoupper(
            $payment->currency
            ?? $booking->currency
            ?? 'USD'
        );

        $amount = number_format($amountCents / 100, 2);

        $seatNames = \Illuminate\Support\Facades\DB::table('booking_seats')
            ->where('booking_id', $booking->id)
            ->join('seats', 'booking_seats.seat_id', '=', 'seats.id')
            ->pluck('seats.seat_number')
            ->implode(', ');
    @endphp

    <div class="mx-auto max-w-[1312px] px-6 pt-10 lg:px-0">
        <div class="rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 sm:p-12">

            <h2 class="text-3xl font-bold">Payment failed</h2>

            <div class="mt-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#e5667a] text-2xl text-[#e5667a]">✕</div>

            <p class="mt-6 text-xs uppercase tracking-wide text-[#9aa3b2]">! Failed · No valid tickets</p>

            <h1 class="mt-6 text-4xl font-bold leading-tight sm:text-5xl">Let's try another way.</h1>

            <p class="mt-6 text-sm text-[#9aa3b2]">
                Your payment was not confirmed as successful. Please check your booking status before trying again.
            </p>

            <div class="mt-6 space-y-5 text-sm text-[#9aa3b2]">
                <p>Booking · {{ $booking->booking_reference }}</p>

                <p>
                    Movie · {{ $movie?->title ?? 'Movie details unavailable' }}
                    @if ($showtime?->starts_at)
                        · {{ \Illuminate\Support\Carbon::parse($showtime->starts_at)->format('M d · H:i') }}
                    @endif
                </p>

                @if ($screen?->name)
                    <p>Screen · {{ $screen->name }}</p>
                @endif

                <p>Seats · {{ $seatNames !== '' ? $seatNames : 'Not available' }}</p>

                <p>
                    Payment · {{ $currency }} {{ $amount }}
                    · {{ ucfirst($payment->status) }}
                </p>
            </div>

            <div class="mt-8 rounded-lg bg-[#17294f] p-4 text-sm leading-relaxed">
                <p class="font-semibold text-blue-300">ⓘ No confirmed booking</p>
                <p>
                    Seat availability depends on the current booking and hold status.
                    Check your booking before selecting seats again.
                </p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('home') }}"
                   class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6]">
                    Choose seats again
                </a>
                <a href="{{ route('dashboard') }}"
                   class="rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c]">
                    My bookings
                </a>
            </div>
        </div>
    </div>
</x-layouts::site>
