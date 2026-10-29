<x-layouts.cinebook :title="__('Late payment - Booking failed')">
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

    <div class="mx-auto max-w-[1312px] px-6 py-10 pb-12 lg:px-0">
        <div class="rounded-2xl border border-[#E7DED2] bg-white p-8 shadow-sm sm:p-12">

            <p class="text-xs font-semibold uppercase tracking-widest text-[#7A6A58]">
                CINEBOOK / PAYMENT UPDATE
            </p>

            <h2 class="mt-4 text-3xl font-bold text-[#252525]">
                Late payment · booking failed
            </h2>

            <div class="mt-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-rose-300 bg-rose-50 text-2xl font-bold text-rose-700">
                !
            </div>

            <p class="mt-6 text-xs font-semibold uppercase tracking-wide text-rose-700">
                Payment failed · Refund {{ $refundStatus }}
            </p>

            <h1 class="mt-4 text-4xl font-bold leading-tight text-[#252525] sm:text-5xl">
                Payment received late.
            </h1>

            <p class="mt-6 text-sm leading-6 text-[#6B6259]">
                This booking is not confirmed. Review the refund status below.
                A refund is only shown here because a refund record exists.
            </p>

            <div class="mt-8 space-y-5 text-sm text-[#6B6259]">
                <p>
                    Verified payment ·
                    <span class="font-semibold text-[#252525]">{{ $currency }} {{ $amount }}</span>
                    · {{ ucfirst($payment->status instanceof \App\Enums\PaymentStatus ? $payment->status->value : $payment->status) }}
                </p>

                <p>
                    Refund ·
                    <span class="font-semibold text-[#252525]">{{ $currency }} {{ $refundAmount }}</span>
                    · {{ $refundStatus }}
                </p>

                <p>
                    Booking ·
                    <span class="font-mono font-semibold text-[#252525]">{{ $booking->booking_reference }}</span>
                </p>

                <p>
                    Movie ·
                    <span class="font-medium text-[#252525]">{{ $movie?->title ?? 'Movie details unavailable' }}</span>
                    @if ($screen?->name)
                        · {{ $screen->name }}
                    @endif
                    @if ($showtime?->starts_at)
                        · {{ \Illuminate\Support\Carbon::parse($showtime->starts_at)->format('M d · H:i') }}
                    @endif
                </p>

                <p>
                    Seats ·
                    <span class="font-medium text-[#252525]">{{ $seatNames !== '' ? $seatNames : 'Not available' }}</span>
                </p>
            </div>

            <div class="mt-8 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm leading-relaxed text-rose-900">
                <p class="font-semibold">! Not a confirmed reservation</p>
                <p class="mt-1">
                    Refund status: {{ $refundStatus }}.
                    A pending refund has not yet been confirmed as completed.
                    Check your booking details for updates.
                </p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('booking.recovery-states', ['booking' => $booking->id]) }}"
                   class="rounded-lg border border-[#E7DED2] bg-[#FAF7F2] px-5 py-3 text-sm font-medium text-[#252525] transition hover:bg-[#F0E9DF]">
                    View refund status
                </a>

                <a href="{{ route('home') }}"
                   class="rounded-lg bg-[#2F6BFF] px-5 py-3 text-sm font-medium text-white transition hover:bg-[#2459D6]">
                    Choose a new show
                </a>
            </div>
        </div>
    </div>
</x-layouts.cinebook>