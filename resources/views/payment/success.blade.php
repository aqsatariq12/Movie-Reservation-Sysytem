
<x-layouts::site :title="$isPaid ? __('Payment successful') : __('Payment processing')">
    @php
        $showtime = $booking->showtime;
        $movie = $showtime?->movie;
        $screen = $showtime?->screen;
        $amount = number_format($payment->amount_cents / 100, 2);
        $currency = strtoupper($payment->currency ?? $booking->currency ?? 'USD');
    @endphp

    <div class="mx-auto max-w-[1312px] px-6 pt-10 lg:px-0">

        <p class="text-xs font-semibold uppercase tracking-wider text-[#7fb0ff]">
            Stripe return / verification
        </p>

        <div class="mt-8 grid items-start gap-8 lg:grid-cols-[minmax(0,832px)_1fr]">

            {{-- Left: payment status --}}
            <div>
                <div class="rounded-2xl border border-[#1f2a44] bg-[#0e1527] p-6">

                    @if ($isPaid)
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-500/15 text-3xl text-green-400">
                            ✓
                        </div>

                        <p class="mt-6 text-xs uppercase tracking-wide text-green-400">
                            Payment confirmed
                        </p>

                        <h1 class="mt-5 text-4xl font-bold leading-tight text-white">
                            Payment successful!
                        </h1>

                        <p class="mt-5 text-sm text-[#9aa3b2]">
                            Your payment has been verified. Your booking is confirmed.
                        </p>

                        <div class="mt-5 rounded-lg border border-green-500/20 bg-green-500/10 p-4 text-sm leading-relaxed text-white">
                            <p class="font-semibold">✓ Booking confirmed</p>
                            <p class="mt-1 text-[#9aa3b2]">
                                Keep your booking reference for future use.
                            </p>
                        </div>
                    @else
                        <div class="h-14 w-14 animate-spin rounded-full border-2 border-[#2a3d66] border-t-[#7fb0ff]"></div>

                        <p class="mt-6 text-xs uppercase tracking-wide text-[#9aa3b2]">
                            Payment pending
                        </p>

                        <h1 class="mt-5 text-4xl font-bold leading-tight text-white">
                            Payment processing
                        </h1>

                        <p class="mt-5 text-sm text-[#9aa3b2]">
                            Your payment has not yet been fully confirmed. Please wait while the payment status is verified.
                        </p>

                        <div class="mt-5 rounded-lg bg-[#17294f] p-4 text-sm leading-relaxed text-white">
                            <p>ⓘ This isn't a ticket confirmation</p>
                            <p class="mt-1 text-[#9aa3b2]">
                                Please don't pay again. Your booking will be confirmed after successful payment verification.
                            </p>
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('booking.recovery-states', $booking) }}"
                           class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6]">
                            Check booking status
                        </a>

                        <a href="{{ route('dashboard') }}"
                           class="rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c]">
                            Go to my bookings
                        </a>
                    </div>
                </div>

                <p class="mt-5 text-sm text-[#9aa3b2]">
                    Booking reference and payment details are shown from your booking records.
                </p>
            </div>

            {{-- Right: actual booking summary --}}
            <div>
                <div class="flex gap-5 rounded-2xl border border-[#1f2a44] bg-[#0e1527] p-6">
                    <div class="flex h-[180px] w-[104px] shrink-0 items-center justify-center rounded-lg bg-gradient-to-b from-[#0f6a9c] via-[#1d3b66] to-[#0a1224] text-4xl text-white">
                        ▶
                    </div>

                    <div class="min-w-0 text-sm leading-7 text-[#9aa3b2]">
                        <h2 class="text-2xl font-bold leading-tight text-white">
                            {{ $movie?->title ?? $movie?->name ?? 'Movie details unavailable' }}
                        </h2>

                        @if ($showtime?->starts_at)
                            <p>
                                {{ \Illuminate\Support\Carbon::parse($showtime->starts_at)->format('D, F j, Y · H:i') }}
                            </p>
                        @else
                            <p>Showtime details unavailable</p>
                        @endif

                        <p>
                            {{ $screen?->name ?? 'Screen details unavailable' }}
                        </p>

                        @if ($movie?->duration_minutes)
                            <p>{{ $movie->duration_minutes }} min</p>
                        @elseif ($movie?->duration)
                            <p>{{ $movie->duration }} min</p>
                        @endif

                        @if ($movie?->rating)
                            <p>{{ $movie->rating }}</p>
                        @endif

                        <p class="break-words">
                            Reference: {{ $booking->booking_reference }}
                        </p>

                        <p class="font-semibold text-white">
                            Total · {{ $currency }} {{ $amount }}
                        </p>

                        <p class="{{ $isPaid ? 'text-green-400' : 'text-amber-400' }}">
                            {{ $isPaid ? 'Paid' : 'Payment pending' }}
                        </p>
                    </div>
                </div>

                <p class="mt-4 break-words text-sm text-[#9aa3b2]">
                    Reference {{ $booking->booking_reference }}
                    · Stripe TEST MODE
                    · {{ $currency }} {{ $amount }}
                </p>
            </div>
        </div>
    </div>
</x-layouts::site>
