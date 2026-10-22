
<x-layouts::site :title="__('Booking recovery states')">
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
    @endphp

    <div class="mx-auto max-w-[1312px] px-6 py-10 lg:px-0">

        {{-- Action result modal --}}
        @if (session('status') || session('error') || $errors->any())
            <div
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="result-modal-title"
            >
                <div class="w-full max-w-md rounded-2xl border border-[#263653] bg-[#0a1020] p-6 shadow-2xl sm:p-8">
                    <div class="mb-5 flex items-center gap-3">
                        @if (session('status'))
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/15 text-2xl text-emerald-400">
                                &#10003;
                            </div>
                        @else
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-500/15 text-2xl text-rose-400">
                                !
                            </div>
                        @endif

                        <h2 id="result-modal-title" class="text-xl font-bold text-white">
                            {{ session('status') ? 'Action successful' : 'Action unsuccessful' }}
                        </h2>
                    </div>

                    <p class="mb-6 break-words text-sm leading-6 text-[#b8c2d4]">
                        {{ session('status') ?? session('error') ?? $errors->first() }}
                    </p>

                    <a
                        href="{{ url()->current() }}"
                        class="block w-full rounded-lg bg-[#2f6bff] px-5 py-3 text-center text-sm font-semibold text-white hover:bg-[#2459d6]"
                    >
                        Back to recovery page
                    </a>
                </div>
            </div>
        @endif

        {{-- Header --}}
        <header class="mb-10">
            <p class="mb-2 font-mono text-xs uppercase tracking-widest text-[#9aa3b2]">
                CINEBOOK / BOOKING RECOVERY
            </p>

            <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">
                Booking status and next steps.
            </h1>

            <p class="mt-3 text-sm text-[#9aa3b2]">
                Booking reference:
                <span class="font-mono text-white">
                    {{ $booking->booking_reference }}
                </span>
            </p>
        </header>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

            {{-- REFUND --}}
            @if ($state === 'refund')
                <section class="rounded-2xl border border-rose-900/60 bg-[#0a1020] p-8 shadow-xl sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-rose-400 text-2xl text-rose-400">
                        !
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-rose-300">
                        REFUND STATUS
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-white">
                        {{ $paymentStatus === 'refunded' ? 'Payment refunded' : 'Refund being processed' }}
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#9aa3b2]">
                        @if ($paymentStatus === 'refunded')
                            The payment record is marked as refunded. Please check your payment provider for the refund details.
                        @else
                            The payment record indicates that a refund is pending. The actual refund must be processed and verified by the payment backend.
                        @endif
                    </p>

                    <div class="mb-8 space-y-3 font-mono text-sm text-[#9aa3b2]">
                        <p>
                            Booking:
                            <span class="text-white">{{ $booking->booking_reference }}</span>
                        </p>
                        <p>
                            Amount:
                            <span class="text-white">{{ $amount }} {{ $currency }}</span>
                        </p>
                        <p>
                            Payment status:
                            <span class="text-white">{{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}</span>
                        </p>
                    </div>

                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-block rounded-lg border border-[#263653] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c]"
                    >
                        View my bookings
                    </a>
                </section>

            {{-- FAILED --}}
            @elseif ($state === 'failed')
                <section class="rounded-2xl border border-rose-900/60 bg-[#0a1020] p-8 shadow-xl sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-rose-400 text-2xl text-rose-400">
                        &#10005;
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-rose-300">
                        PAYMENT FAILED
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-white">
                        Your payment needs attention.
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#9aa3b2]">
                        The payment record is marked as failed. Check your booking status before trying another payment.
                    </p>

                    <div class="mb-8 space-y-3 font-mono text-sm text-[#9aa3b2]">
                        <p>
                            Booking:
                            <span class="text-white">{{ $booking->booking_reference }}</span>
                        </p>
                        <p>
                            Amount:
                            <span class="text-white">{{ $amount }} {{ $currency }}</span>
                        </p>
                        <p>
                            Payment:
                            <span class="text-white">{{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}</span>
                        </p>
                    </div>

                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-block rounded-lg border border-[#263653] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c]"
                    >
                        View my bookings
                    </a>
                </section>

            {{-- PAID --}}
            @elseif ($state === 'paid')
                <section class="rounded-2xl border border-emerald-900/60 bg-[#0a1020] p-8 shadow-xl sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-emerald-400 text-2xl text-emerald-400">
                        &#10003;
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-emerald-300">
                        PAYMENT CONFIRMED
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-white">
                        Your booking is confirmed.
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#9aa3b2]">
                        Your booking or payment record is marked as confirmed. Check your booking details for ticket availability.
                    </p>

                    <div class="mb-8 space-y-3 font-mono text-sm text-[#9aa3b2]">
                        <p>
                            Booking:
                            <span class="text-white">{{ $booking->booking_reference }}</span>
                        </p>
                        <p>
                            Amount:
                            <span class="text-white">{{ $amount }} {{ $currency }}</span>
                        </p>
                        <p>
                            Payment:
                            <span class="text-white">{{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}</span>
                        </p>
                    </div>

                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-block rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6]"
                    >
                        View my bookings
                    </a>
                </section>

            {{-- EXPIRED / CANCELLED --}}
            @elseif ($state === 'expired')
                <section class="rounded-2xl border border-amber-900/60 bg-[#0a1020] p-8 shadow-xl sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-amber-400 text-2xl text-amber-400">
                        !
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-amber-300">
                        HOLD EXPIRED / BOOKING CANCELLED
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-white">
                        This booking is no longer active.
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#9aa3b2]">
                        The hold may have expired or the booking may have been cancelled. Check seat availability before starting a new booking.
                    </p>

                    <div class="mb-8 space-y-3 font-mono text-sm text-[#9aa3b2]">
                        <p>
                            Booking:
                            <span class="text-white">{{ $booking->booking_reference }}</span>
                        </p>

                        <p>
                            Booking status:
                            <span class="text-white">{{ ucfirst(str_replace('_', ' ', $bookingStatus)) }}</span>
                        </p>

                        @if ($hold?->expires_at)
                            <p>
                                Hold expiry:
                                <span class="text-white">
                                    {{ $hold->expires_at->format('d M Y, H:i:s') }}
                                </span>
                            </p>
                        @endif
                    </div>

                    <div class="mb-8 rounded-lg bg-[#17294f] p-4 text-sm leading-relaxed">
                        <p class="font-semibold text-blue-300">Start with available seats</p>
                        <p class="mt-1 text-[#9aa3b2]">
                            This page will not start a new payment for a cancelled booking.
                        </p>
                    </div>

                    <a
                        href="{{ route('home') }}"
                        class="inline-block rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6]"
                    >
                        Find available seats
                    </a>
                </section>

            {{-- ACTIVE CHECKOUT --}}
            @elseif ($state === 'active')
                <section class="rounded-2xl border border-sky-900/60 bg-[#0a1020] p-8 shadow-xl sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-sky-400 text-2xl text-sky-400">
                        &#9203;
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-sky-300">
                        PENDING PAYMENT / NOT CONFIRMED
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-white">
                        Your hold is still active.
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#9aa3b2]">
                        Complete Stripe Checkout before your hold expires. Returning from Stripe or closing Checkout does not itself confirm payment.
                    </p>

                    <div class="mb-6 space-y-3 font-mono text-sm text-[#9aa3b2]">
                        <p>
                            Booking:
                            <span class="text-white">{{ $booking->booking_reference }}</span>
                        </p>

                        <p>
                            Amount:
                            <span class="text-white">{{ $amount }} {{ $currency }}</span>
                        </p>

                        <p>
                            Time remaining:
                            <span
                                id="hold-countdown"
                                class="text-white"
                                data-expires-at="{{ $expiryTimestamp }}"
                            >Calculating...</span>
                        </p>

                        <p>
                            Hold expires:
                            <span class="text-white">
                                {{ $hold->expires_at->format('d M Y, H:i:s') }}
                            </span>
                        </p>
                    </div>

                    <div class="mb-8 rounded-lg bg-[#17294f] p-4 text-sm leading-relaxed">
                        <p class="font-semibold text-blue-300">
                            Resume checkout while the hold is active
                        </p>
                        <p class="mt-1 text-[#9aa3b2]">
                            The countdown uses the expiry timestamp stored in your database.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('payment.checkout', $booking) }}">
                            @csrf

                            <button
                                type="submit"
                                class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-semibold text-white hover:bg-[#2459d6]"
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
                                class="rounded-lg border border-rose-700 bg-rose-950/40 px-5 py-3 text-sm font-semibold text-rose-300 hover:bg-rose-900/60"
                            >
                                Release Hold
                            </button>
                        </form>
                    </div>
                </section>

            {{-- UNKNOWN --}}
            @else
                <section class="rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 shadow-xl sm:p-10">
                    <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-slate-400 text-2xl text-slate-300">
                        i
                    </div>

                    <p class="mb-2 font-mono text-xs uppercase tracking-wide text-[#9aa3b2]">
                        BOOKING STATUS
                    </p>

                    <h2 class="mb-4 text-3xl font-bold text-white">
                        Check your booking details.
                    </h2>

                    <p class="mb-6 text-sm leading-relaxed text-[#9aa3b2]">
                        The current booking and payment statuses do not match a recovery action available on this page.
                    </p>

                    <div class="mb-8 space-y-3 font-mono text-sm text-[#9aa3b2]">
                        <p>
                            Booking:
                            <span class="text-white">{{ $booking->booking_reference }}</span>
                        </p>
                        <p>
                            Booking status:
                            <span class="text-white">{{ ucfirst(str_replace('_', ' ', $bookingStatus)) }}</span>
                        </p>
                        <p>
                            Payment status:
                            <span class="text-white">{{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}</span>
                        </p>
                        <p>
                            Amount:
                            <span class="text-white">{{ $amount }} {{ $currency }}</span>
                        </p>
                    </div>

                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-block rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6]"
                    >
                        View my bookings
                    </a>
                </section>
            @endif

            {{-- BOOKING SUMMARY --}}
            <section class="rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 shadow-xl sm:p-10">
                <h2 class="mb-6 text-2xl font-bold text-white">
                    Booking summary
                </h2>

                <div class="space-y-4 text-sm text-[#9aa3b2]">
                    <div class="flex flex-wrap justify-between gap-3 border-b border-[#1f2a44] pb-4">
                        <span>Booking reference</span>
                        <span class="font-mono text-white">
                            {{ $booking->booking_reference }}
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-between gap-3 border-b border-[#1f2a44] pb-4">
                        <span>Booking status</span>
                        <span class="text-white">
                            {{ ucfirst(str_replace('_', ' ', $bookingStatus)) }}
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-between gap-3 border-b border-[#1f2a44] pb-4">
                        <span>Payment status</span>
                        <span class="text-white">
                            {{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-between gap-3 border-b border-[#1f2a44] pb-4">
                        <span>Total amount</span>
                        <span class="text-white">
                            {{ $amount }} {{ $currency }}
                        </span>
                    </div>

                    @if ($payment?->provider)
                        <div class="flex flex-wrap justify-between gap-3 border-b border-[#1f2a44] pb-4">
                            <span>Payment provider</span>
                            <span class="text-white">
                                {{ ucfirst($payment->provider) }}
                            </span>
                        </div>
                    @endif

                    @if ($hold?->status)
                        <div class="flex flex-wrap justify-between gap-3 border-b border-[#1f2a44] pb-4">
                            <span>Hold status</span>
                            <span class="text-white">
                                {{ ucfirst($hold->status) }}
                            </span>
                        </div>
                    @endif

                    @if ($hold?->expires_at)
                        <div class="flex flex-wrap justify-between gap-3">
                            <span>Hold expiry</span>
                            <span class="text-right text-white">
                                {{ $hold->expires_at->format('d M Y, H:i:s') }}
                            </span>
                        </div>
                    @endif
                </div>

                <a
                    href="{{ route('dashboard') }}"
                    class="mt-8 inline-block rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c]"
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
</x-layouts::site>
