<x-layouts.cinebook :title="__('Payment cancelled')">
    @php
        $showtime = $booking->showtime;
        $movie = $showtime?->movie;
        $screen = $showtime?->screen;

        $amountCents = $payment?->amount_cents
            ?? $booking->total_amount_cents
            ?? 0;

        $currency = strtoupper(
            $payment?->currency
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

    <div class="mx-auto max-w-[1312px] px-6 py-10 lg:px-0">
        <div class="rounded-2xl border border-[#E7DED2] bg-white p-8 text-[#252525] shadow-sm sm:p-12">

            <h2 class="text-3xl font-bold">Payment cancelled</h2>

            <div class="mt-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-red-400 bg-red-50 text-2xl text-red-600">
                ✕
            </div>

            <p class="mt-6 text-xs font-semibold uppercase tracking-wide text-red-600">
                Checkout cancelled · No payment confirmation
            </p>

            <h1 class="mt-6 text-4xl font-bold leading-tight sm:text-5xl">
                Your payment wasn't completed.
            </h1>

            <p class="mt-6 text-sm leading-relaxed text-[#6B6259]">
                You returned from Stripe Checkout before completing payment.
                This page does not confirm a successful payment. Check your
                booking status before trying again.
            </p>

            <div class="mt-6 space-y-4 text-sm text-[#6B6259]">
                <p>
                    <span class="font-semibold text-[#252525]">Booking ·</span>
                    {{ $booking->booking_reference }}
                </p>

                <p>
                    <span class="font-semibold text-[#252525]">Movie ·</span>
                    {{ $movie?->title ?? 'Movie details unavailable' }}
                </p>

                @if ($screen?->name)
                    <p>
                        <span class="font-semibold text-[#252525]">Screen ·</span>
                        {{ $screen->name }}
                    </p>
                @endif

                @if ($showtime)
                    <p>
                        <span class="font-semibold text-[#252525]">Show time ·</span>
                        {{ $showtime->starts_at ?? 'Not available' }}
                    </p>
                @endif

                <p>
                    <span class="font-semibold text-[#252525]">Seats ·</span>
                    {{ $seatNames !== '' ? $seatNames : 'Not available' }}
                </p>

                <p>
                    <span class="font-semibold text-[#252525]">Payment ·</span>
                    {{ $currency }} {{ $amount }}
                    · {{ ucfirst($payment?->status instanceof \App\Enums\PaymentStatus
                        ? $payment->status->value
                        : ($payment?->status ?? 'pending')) }}
                </p>

                <p>
                    <span class="font-semibold text-[#252525]">Booking status ·</span>
                    {{ ucfirst(str_replace('_', ' ', $booking->status instanceof \BackedEnum
                        ? $booking->status->value
                        : $booking->status)) }}
                </p>
            </div>

            <div class="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm leading-relaxed text-blue-900">
                <p class="font-semibold">ⓘ Please check your booking</p>
                <p class="mt-2">
                    Cancelling Checkout does not itself confirm that your
                    booking hold was released. Seat availability depends on
                    the current booking and hold status.
                </p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('booking.recovery-states', ['booking' => $booking->id]) }}"
                   class="inline-flex items-center justify-center rounded-lg bg-[#2F6BFF] px-5 py-3 text-sm font-medium text-white transition hover:bg-[#2459d6]">
                    View booking status
                </a>

                <a href="{{ route('bookings.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-[#E7DED2] bg-white px-5 py-3 text-sm font-medium text-[#252525] transition hover:bg-[#F8F5F0]">
                    My bookings
                </a>
            </div>
        </div>
    </div>
</x-layouts.cinebook>
