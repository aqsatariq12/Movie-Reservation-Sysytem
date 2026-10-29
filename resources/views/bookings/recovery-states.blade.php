<x-layouts.cinebook :title="__('Booking recovery states')">
    @php
        $payment = $booking->payments->sortByDesc('id')->first();
        $hold = $booking->hold;

        $amount = number_format(
            ((int) $booking->total_amount_cents) / 100,
            2
        );

        $currency = strtoupper($booking->currency ?: 'USD');
        $bookingStatus = $booking->status;
        $paymentStatus = $payment?->status ?? 'unknown';

        $holdActive = $hold
            && $hold->status === 'active'
            && $hold->expires_at
            && $hold->expires_at->isFuture();

        // Refund and failure must take priority over paid status.
        if (in_array($paymentStatus, ['refunded', 'refund_pending'], true)) {
            $state = 'refund';
        } elseif (
            $paymentStatus === 'failed'
            || $bookingStatus === 'payment_failed'
        ) {
            $state = 'failed';
        } elseif (
            $bookingStatus === 'confirmed'
            || $paymentStatus === 'paid'
        ) {
            $state = 'paid';
        } elseif (
            $bookingStatus === 'cancelled'
            || (
                $hold
                && $hold->expires_at
                && $hold->expires_at->isPast()
            )
        ) {
            $state = 'expired';
        } elseif (
            $bookingStatus === 'pending_payment'
            && $holdActive
            && in_array($paymentStatus, ['unknown', 'pending'], true)
        ) {
            $state = 'active';
        } else {
            $state = 'unknown';
        }

        $expiryTimestamp = $hold?->expires_at?->timestamp;

        $bookingStatusLabel = ucfirst(str_replace(
            '_',
            ' ',
            $bookingStatus instanceof \App\Enums\BookingStatus
                ? $bookingStatus->value
                : $bookingStatus
        ));

        $paymentStatusLabel = ucfirst(str_replace(
            '_',
            ' ',
            $paymentStatus instanceof \App\Enums\PaymentStatus
                ? $paymentStatus->value
                : $paymentStatus
        ));
    @endphp

    <div class="mx-auto max-w-[1312px] px-6 py-10 pb-12 lg:px-0">

        {{-- Action result modal --}}
        @if (session('status') || session('error') || $errors->any())
            <div
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-labelledby="result-modal-title"
            >
                <div class="w-full max-w-md rounded-2xl border border-[#E7DED2] bg-white p-6 shadow-2xl sm:p-8">
                    <div class="mb-5 flex items-center gap-3">
                        @if (session('status'))
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-700">
                                &#10003;
                            </div>
                        @else
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-2xl text-rose-700">
                                !
                            </div>
                        @endif

                        <h2 id="result-modal-title" class="text-xl font-bold text-[#252525]">
                            {{ session('status') ? 'Action successful' : 'Action unsuccessful' }}
                        </h2>
                    </div>

                    <p class="mb-6 break-words text-sm leading-6 text-[#6B6259]">
                        {{ session('status') ?? session('error') ?? $errors->first() }}
                    </p>

                    <a
                        href="{{ url()->current() }}"
                        class="block w-full rounded-lg bg-[#2F6BFF] px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-[#2459D6]"
                    >
                        Back to recovery page
                    </a>
                </div>
            </div>
        @endif

        {{-- Header --}}
        <header class="mb-10">
            <p class="mb-2 font-mono text-xs uppercase tracking-widest text-[#7A6A58]">
                CINEBOOK / BOOKING RECOVERY
            </p>

            <h1 class="text-4xl font-bold tracking-tight text-[#252525] sm:text-5xl">
                Booking status and next steps.
            </h1>

            <p class="mt-3 text-sm text-[#6B6259]">
                Booking reference:
                <span class="font-mono font-semibold text-[#252525]">
                    {{ $booking->booking_reference }}
                </span>
            </p>
        </header>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

            {{-- REFUND --}}
            @if ($state === 'refund')
                <section class="rounded-2xl border border-rose-200 bg-white p-8 shadow-sm sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-rose-300 bg-rose-50 text-2xl text-rose-700">
                        !
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-rose-700">
                        REFUND STATUS
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-[#252525]">
                        {{ $paymentStatus === 'refunded' ? 'Payment refunded' : 'Refund being processed' }}
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#6B6259]">
                        @if ($paymentStatus === 'refunded')
                            The payment record is marked as refunded. Please check your payment provider for the refund details.
                        @else
                            The payment record indicates that a refund is pending. The actual refund must be processed and verified by the payment backend.
                        @endif
                    </p>

                    <div class="mb-8 space-y-3 font-mono text-sm text-[#6B6259]">
                        <p>
                            Booking:
                            <span class="break-words text-[#252525]">{{ $booking->booking_reference }}</span>
                        </p>
                        <p>
                            Amount:
                            <span class="text-[#252525]">{{ $amount }} {{ $currency }}</span>
                        </p>
                        <p>
                            Payment status:
                            <span class="text-[#252525]">{{ $paymentStatusLabel }}</span>
                        </p>
                    </div>

                    <a
                        href="{{ route('bookings.index') }}"
                        class="inline-block rounded-lg border border-[#E7DED2] bg-[#FAF7F2] px-5 py-3 text-sm font-medium text-[#252525] transition hover:bg-[#F0E9DF]"
                    >
                        View my bookings
                    </a>
                </section>

            {{-- FAILED --}}
            @elseif ($state === 'failed')
                <section class="rounded-2xl border border-rose-200 bg-white p-8 shadow-sm sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-rose-300 bg-rose-50 text-2xl text-rose-700">
                        &#10005;
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-rose-700">
                        PAYMENT FAILED
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-[#252525]">
                        Your payment needs attention.
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#6B6259]">
                        The payment record is marked as failed. Check your booking status before trying another payment.
                    </p>

                    <div class="mb-8 space-y-3 font-mono text-sm text-[#6B6259]">
                        <p>
                            Booking:
                            <span class="break-words text-[#252525]">{{ $booking->booking_reference }}</span>
                        </p>
                        <p>
                            Amount:
                            <span class="text-[#252525]">{{ $amount }} {{ $currency }}</span>
                        </p>
                        <p>
                            Payment:
                            <span class="text-[#252525]">{{ $paymentStatusLabel }}</span>
                        </p>
                    </div>

                    <a
                        href="{{ route('bookings.index') }}"
                        class="inline-block rounded-lg border border-[#E7DED2] bg-[#FAF7F2] px-5 py-3 text-sm font-medium text-[#252525] transition hover:bg-[#F0E9DF]"
                    >
                        View my bookings
                    </a>
                </section>

            {{-- PAID --}}
            @elseif ($state === 'paid')
                <section class="rounded-2xl border border-emerald-200 bg-white p-8 shadow-sm sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-emerald-300 bg-emerald-50 text-2xl text-emerald-700">
                        &#10003;
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-emerald-700">
                        PAYMENT CONFIRMED
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-[#252525]">
                        Your booking is confirmed.
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#6B6259]">
                        Your booking or payment record is marked as confirmed. Check your booking details for ticket availability.
                    </p>

                    <div class="mb-8 space-y-3 font-mono text-sm text-[#6B6259]">
                        <p>
                            Booking:
                            <span class="break-words text-[#252525]">{{ $booking->booking_reference }}</span>
                        </p>
                        <p>
                            Amount:
                            <span class="text-[#252525]">{{ $amount }} {{ $currency }}</span>
                        </p>
                        <p>
                            Payment:
                            <span class="text-[#252525]">{{ $paymentStatusLabel }}</span>
                        </p>
                    </div>

                    <a
                        href="{{ route('bookings.index') }}"
                        class="inline-block rounded-lg bg-[#2F6BFF] px-5 py-3 text-sm font-medium text-white transition hover:bg-[#2459D6]"
                    >
                        View my bookings
                    </a>
                </section>

            {{-- EXPIRED / CANCELLED --}}
            @elseif ($state === 'expired')
                <section class="rounded-2xl border border-amber-200 bg-white p-8 shadow-sm sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-amber-300 bg-amber-50 text-2xl text-amber-700">
                        !
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-amber-700">
                        HOLD EXPIRED / BOOKING CANCELLED
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-[#252525]">
                        This booking is no longer active.
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#6B6259]">
                        The hold may have expired or the booking may have been cancelled. Check seat availability before starting a new booking.
                    </p>

                    <div class="mb-8 space-y-3 font-mono text-sm text-[#6B6259]">
                        <p>
                            Booking:
                            <span class="break-words text-[#252525]">{{ $booking->booking_reference }}</span>
                        </p>

                        <p>
                            Booking status:
                            <span class="text-[#252525]">{{ $bookingStatusLabel }}</span>
                        </p>

                        @if ($hold?->expires_at)
                            <p>
                                Hold expiry:
                                <span class="text-[#252525]">
                                    {{ $hold->expires_at->format('d M Y, H:i:s') }}
                                </span>
                            </p>
                        @endif
                    </div>

                    <div class="mb-8 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm leading-relaxed">
                        <p class="font-semibold text-blue-900">Start with available seats</p>
                        <p class="mt-1 text-blue-800">
                            This page will not start a new payment for a cancelled booking.
                        </p>
                    </div>

                    <a
                        href="{{ route('showtimes.seats', $booking->showtime_id) }}"
                        class="inline-block rounded-lg bg-[#2F6BFF] px-5 py-3 text-sm font-medium text-white transition hover:bg-[#2459D6]"
                    >
                        Find available seats
                    </a>
                </section>

            {{-- ACTIVE CHECKOUT --}}
            @elseif ($state === 'active')
                <section class="rounded-2xl border border-sky-200 bg-white p-8 shadow-sm sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-sky-300 bg-sky-50 text-2xl text-sky-700">
                        &#9203;
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-sky-700">
                        PENDING PAYMENT / NOT CONFIRMED
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-[#252525]">
                        Your hold is still active.
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#6B6259]">
                        Complete Stripe Checkout before your hold expires. Returning from Stripe or closing Checkout does not itself confirm payment.
                    </p>

                    <div class="mb-6 space-y-3 font-mono text-sm text-[#6B6259]">
                        <p>
                            Booking:
                            <span class="break-words text-[#252525]">{{ $booking->booking_reference }}</span>
                        </p>

                        <p>
                            Amount:
                            <span class="text-[#252525]">{{ $amount }} {{ $currency }}</span>
                        </p>

                        <p>
                            Time remaining:
                            <span
                                id="hold-countdown"
                                class="font-semibold text-[#252525]"
                                data-expires-at="{{ $expiryTimestamp }}"
                            >Calculating...</span>
                        </p>

                        <p>
                            Hold expires:
                            <span class="text-[#252525]">
                                {{ $hold->expires_at->format('d M Y, H:i:s') }}
                            </span>
                        </p>
                    </div>

                    <div class="mb-8 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm leading-relaxed">
                        <p class="font-semibold text-blue-900">
                            Resume checkout while the hold is active
                        </p>
                        <p class="mt-1 text-blue-800">
                            The countdown uses the expiry timestamp stored in your database.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('payment.checkout', $booking) }}">
                            @csrf

                            <button
                                type="submit"
                                class="rounded-lg bg-[#2F6BFF] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#2459D6]"
                            >
                                Resume Stripe Checkout
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('booking.release-hold', $booking) }}"
                            onsubmit="return confirm('Are you sure you want to release this hold? Your booking will be cancelled and its held seats released.');"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="rounded-lg border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                            >
                                Release Hold
                            </button>
                        </form>
                    </div>
                </section>

            {{-- UNKNOWN --}}
            @else
                <section class="rounded-2xl border border-[#E7DED2] bg-white p-8 shadow-sm sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#D6CEC2] bg-[#FAF7F2] text-2xl text-[#6B6259]">
                        i
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-[#7A6A58]">
                        BOOKING STATUS
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-[#252525]">
                        Check your booking details.
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#6B6259]">
                        The current booking and payment statuses do not match a recovery action available on this page.
                    </p>

                    <div class="mb-8 space-y-3 font-mono text-sm text-[#6B6259]">
                        <p>
                            Booking:
                            <span class="break-words text-[#252525]">{{ $booking->booking_reference }}</span>
                        </p>
                        <p>
                            Booking status:
                            <span class="text-[#252525]">{{ $bookingStatusLabel }}</span>
                        </p>
                        <p>
                            Payment status:
                            <span class="text-[#252525]">{{ $paymentStatusLabel }}</span>
                        </p>
                        <p>
                            Amount:
                            <span class="text-[#252525]">{{ $amount }} {{ $currency }}</span>
                        </p>
                    </div>

                    <a
                        href="{{ route('bookings.index') }}"
                        class="inline-block rounded-lg bg-[#2F6BFF] px-5 py-3 text-sm font-medium text-white transition hover:bg-[#2459D6]"
                    >
                        View my bookings
                    </a>
                </section>
            @endif

            {{-- BOOKING SUMMARY --}}
            <section class="rounded-2xl border border-[#E7DED2] bg-white p-8 shadow-sm sm:p-10">
                <h2 class="mb-6 text-2xl font-bold text-[#252525]">
                    Booking summary
                </h2>

                <div class="space-y-4 text-sm text-[#6B6259]">
                    <div class="flex flex-wrap justify-between gap-3 border-b border-[#E7DED2] pb-4">
                        <span>Booking reference</span>
                        <span class="break-words font-mono text-[#252525]">
                            {{ $booking->booking_reference }}
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-between gap-3 border-b border-[#E7DED2] pb-4">
                        <span>Booking status</span>
                        <span class="text-[#252525]">
                            {{ $bookingStatusLabel }}
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-between gap-3 border-b border-[#E7DED2] pb-4">
                        <span>Payment status</span>
                        <span class="text-[#252525]">
                            {{ $paymentStatusLabel }}
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-between gap-3 border-b border-[#E7DED2] pb-4">
                        <span>Total amount</span>
                        <span class="text-[#252525]">
                            {{ $amount }} {{ $currency }}
                        </span>
                    </div>

                    @if ($payment?->provider)
                        <div class="flex flex-wrap justify-between gap-3 border-b border-[#E7DED2] pb-4">
                            <span>Payment provider</span>
                            <span class="text-[#252525]">
                                {{ ucfirst($payment->provider) }}
                            </span>
                        </div>
                    @endif

                    @if ($hold?->status)
                        <div class="flex flex-wrap justify-between gap-3 border-b border-[#E7DED2] pb-4">
                            <span>Hold status</span>
                            <span class="text-[#252525]">
                                {{ ucfirst(str_replace('_', ' ', $hold->status instanceof \App\Enums\HoldStatus ? $hold->status->value : $hold->status)) }}
                            </span>
                        </div>
                    @endif

                    @if ($hold?->expires_at)
                        <div class="flex flex-wrap justify-between gap-3">
                            <span>Hold expiry</span>
                            <span class="text-right text-[#252525]">
                                {{ $hold->expires_at->format('d M Y, H:i:s') }}
                            </span>
                        </div>
                    @endif
                </div>

                <a
                    href="{{ route('bookings.index') }}"
                    class="mt-8 inline-block rounded-lg border border-[#E7DED2] bg-[#FAF7F2] px-5 py-3 text-sm font-medium text-[#252525] transition hover:bg-[#F0E9DF]"
                >
                    My bookings
                </a>
            </section>
        </div>
    </div>

    {{-- Live hold countdown --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const countdown = document.getElementById('hold-countdown');

            if (!countdown) {
                return;
            }

            const expirySeconds = Number(countdown.dataset.expiresAt);

            if (!Number.isFinite(expirySeconds)) {
                countdown.textContent = 'Expiry unavailable';
                return;
            }

            const updateCountdown = () => {
                const remaining = expirySeconds - Math.floor(Date.now() / 1000);

                if (remaining <= 0) {
                    countdown.textContent = 'Hold expired — refresh the page';
                    return;
                }

                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;

                countdown.textContent =
                    `${String(hours).padStart(2, '0')}:` +
                    `${String(minutes).padStart(2, '0')}:` +
                    `${String(seconds).padStart(2, '0')}`;

                window.setTimeout(updateCountdown, 1000);
            };

            updateCountdown();
        });
    </script>
</x-layouts.cinebook>