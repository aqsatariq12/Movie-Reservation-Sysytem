 <x-layouts.cinebook :title="$isPaid ? __('Payment successful') : __('Payment processing')">
    @php
        $showtime = $booking->showtime;
        $movie = $showtime?->movie;
        $screen = $showtime?->screen;
        $amount = number_format($payment->amount_cents / 100, 2);
        $currency = strtoupper($payment->currency ?? $booking->currency ?? 'USD');
    @endphp

    <div class="mx-auto max-w-[1312px] px-6 pt-10 pb-12 lg:px-0">

        <p class="text-xs font-semibold uppercase tracking-wider text-[#7A6A58]">
            Stripe return / verification
        </p>

        <div class="mt-8 grid items-start gap-8 lg:grid-cols-[minmax(0,832px)_1fr]">

            {{-- Left: payment status --}}
            <div>
                <div class="rounded-2xl border border-[#E7DED2] bg-white p-6 shadow-sm">

                    @if ($isPaid)
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-3xl text-green-700">
                            ✓
                        </div>

                        <p class="mt-6 text-xs font-semibold uppercase tracking-wide text-green-700">
                            Payment confirmed
                        </p>

                        <h1 class="mt-5 text-4xl font-bold leading-tight text-[#252525]">
                            Payment successful!
                        </h1>

                        <p class="mt-5 text-sm leading-6 text-[#6B6259]">
                            Your payment has been verified. Your booking is confirmed.
                        </p>

                        <div class="mt-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm leading-relaxed text-green-900">
                            <p class="font-semibold">✓ Booking confirmed</p>
                            <p class="mt-1 text-green-800">
                                Keep your booking reference for future use.
                            </p>
                        </div>
                    @else
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50">
                            <div class="h-8 w-8 animate-spin rounded-full border-2 border-[#D6E2F0] border-t-[#2F6BFF]"></div>
                        </div>

                        <p class="mt-6 text-xs font-semibold uppercase tracking-wide text-[#7A6A58]">
                            Payment pending
                        </p>

                        <h1 class="mt-5 text-4xl font-bold leading-tight text-[#252525]">
                            Payment processing
                        </h1>

                        <p class="mt-5 text-sm leading-6 text-[#6B6259]">
                            Your payment has not yet been fully confirmed. Please wait while the payment status is verified.
                        </p>

                        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-relaxed text-amber-950">
                            <p class="font-semibold">ⓘ This isn't a ticket confirmation</p>
                            <p class="mt-1 text-amber-900">
                                Please don't pay again. Your booking will be confirmed after successful payment verification.
                            </p>
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('booking.recovery-states', $booking) }}"
                            class="rounded-lg bg-[#2F6BFF] px-5 py-3 text-sm font-medium text-white transition hover:bg-[#2459D6]">
                            Check booking status
                        </a>

                        <a href="{{ url('/my-bookings') }}"
                            class="rounded-lg border border-[#E7DED2] bg-[#FAF7F2] px-5 py-3 text-sm font-medium text-[#252525] transition hover:bg-[#F0E9DF]">
                            Go to my bookings
                        </a>
                    </div>
                </div>

                <p class="mt-5 text-sm leading-6 text-[#7A6A58]">
                    Booking reference and payment details are shown from your booking records.
                </p>
            </div>

            {{-- Right: actual booking summary --}}
            <div>
                <div class="flex gap-5 rounded-2xl border border-[#E7DED2] bg-white p-6 shadow-sm">
                    <div class="h-[180px] w-[104px] shrink-0 overflow-hidden rounded-lg bg-[#F5F0E8]">
                        @if ($movie?->poster_url)
                            <img src="{{ $movie->poster_url }}" alt="{{ $movie->title ?? 'Movie poster' }}"
                                class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-b from-[#E5F0F8] via-[#D7E5F3] to-[#F5F0E8] text-4xl text-[#2F6BFF]">
                                ▶
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 text-sm leading-7 text-[#6B6259]">
                        <h2 class="text-2xl font-bold leading-tight text-[#252525]">
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

                        <p class="font-semibold text-[#252525]">
                            Total · {{ $currency }} {{ $amount }}
                        </p>

                        <p class="{{ $isPaid ? 'font-semibold text-green-700' : 'font-semibold text-amber-700' }}">
                            {{ $isPaid ? 'Paid' : 'Payment pending' }}
                        </p>
                    </div>
                </div>

                <p class="mt-4 break-words text-sm leading-6 text-[#7A6A58]">
                    Reference {{ $booking->booking_reference }}
                    · Stripe TEST MODE
                    · {{ $currency }} {{ $amount }}
                </p>
            </div>
        </div>
    </div>
</x-layouts.cinebook>