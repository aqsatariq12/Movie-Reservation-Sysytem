<x-layouts::site :title="__('Late payment - Booking failed')">
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

        $latestRefund = $refunds->first();

        $refundAmount = $latestRefund
            ? number_format($latestRefund->amount_cents / 100, 2)
            : '0.00';

        $refundStatus = $latestRefund
            ? ucfirst(str_replace('_', ' ', $latestRefund->status))
            : 'Unknown';

        $seatNames = \Illuminate\Support\Facades\DB::table('booking_seats')
            ->where('booking_id', $booking->id)
            ->join('seats', 'booking_seats.seat_id', '=', 'seats.id')
            ->pluck('seats.seat_number')
            ->implode(', ');
    @endphp

    <div class="mx-auto max-w-[1312px] px-6 pt-10 lg:px-0">
        <div class="rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 sm:p-12">

            <h2 class="text-3xl font-bold">Late payment · booking failed</h2>

            <div class="mt-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#f43f5e] text-2xl font-bold text-[#f43f5e]">!</div>

            <p class="mt-6 text-xs uppercase tracking-wide text-[#9aa3b2]">
                ! Failed · Refund {{ $refundStatus }}
            </p>

            <h1 class="mt-6 text-4xl font-bold leading-tight sm:text-5xl">
                Payment received late.
            </h1>

            <p class="mt-6 text-sm text-[#9aa3b2]">
                This booking is not confirmed. Review the refund status below.
                A refund is only shown here because a refund record exists.
            </p>

            <div class="mt-6 space-y-5 text-sm text-[#9aa3b2]">
                <p>
                    Verified payment · {{ $currency }} {{ $amount }}
                    · {{ ucfirst($payment->status) }}
                </p>

                <p>
                    Refund · {{ $currency }} {{ $refundAmount }}
                    · {{ $refundStatus }}
                </p>

                <p>Booking · {{ $booking->booking_reference }}</p>

                <p>
                    Movie · {{ $movie?->title ?? 'Movie details unavailable' }}
                    @if ($screen?->name)
                        · {{ $screen->name }}
                    @endif
                    @if ($showtime?->starts_at)
                        · {{ \Illuminate\Support\Carbon::parse($showtime->starts_at)->format('M d · H:i') }}
                    @endif
                </p>

                <p>Seats · {{ $seatNames !== '' ? $seatNames : 'Not available' }}</p>
            </div>

            <div class="mt-8 rounded-lg border border-rose-900/50 bg-rose-950/30 p-4 text-sm leading-relaxed text-rose-200">
                <p class="font-semibold">! Not a confirmed reservation</p>
                <p>
                    Refund status: {{ $refundStatus }}.
                    A pending refund has not yet been confirmed as completed.
                    Check your booking details for updates.
                </p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('booking.recovery-states', ['booking' => $booking->id]) }}"
                   class="rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c]">
                    View refund status
                </a>
                <a href="{{ route('home') }}"
                   class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6]">
                    Choose a new show
                </a>
            </div>
        </div>
    </div>
</x-layouts::site>
