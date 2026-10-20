<x-layouts::site :title="__('Booking recovery states')">
    <div class="mx-auto max-w-[1312px] px-6 py-10 lg:px-0">
        
{{-- Success / Error Modal --}}
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
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-2xl text-emerald-400">
                        ✓
                    </div>
                @else
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-500/15 text-2xl text-rose-400">
                        !
                    </div>
                @endif

                <h2 id="result-modal-title" class="text-xl font-bold text-white">
                    {{ session('status') ? 'Hold released successfully' : 'Unable to release hold' }}
                </h2>
            </div>

            <p class="mb-6 break-words text-sm leading-6 text-[#b8c2d4]">
                {{ session('status') ?? session('error') ?? $errors->first() }}
            </p>

            <a
                href="{{ url()->current() }}"
                class="block w-full rounded-lg bg-[#2f6bff] px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-[#2459d6]"
            >
                Back to recovery page
            </a>
        </div>
    </div>
@endif

        
        {{-- Top Header Section --}}
        <div class="mb-10">
            <p class="text-xs uppercase tracking-widest text-[#9aa3b2] font-mono mb-2">&mdash; CINEBOOK &bull; COMPANION STATUS</p>
            <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">Four outcomes. Four clear next steps.</h1>
        </div>

        {{-- 2x2 Grid Layout --}}
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

            {{-- 1. PAYMENT FAILED --}}
            <div class="flex flex-col justify-between rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 sm:p-10 shadow-xl">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-6">Payment failed</h2>

                    <div class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#e5667a] text-2xl text-[#e5667a] mb-6">✕</div>

                    <p class="text-xs font-mono uppercase tracking-wide text-[#9aa3b2] mb-2">! FAILED &bull; NO VALID TICKETS</p>

                    <h3 class="text-3xl font-bold leading-tight text-white mb-4">Let's try another way.</h3>

                    <p class="text-sm text-[#9aa3b2] leading-relaxed mb-6">
                        Stripe reported that your test payment failed. Your hold was released. None of your selected seats remain reserved.
                    </p>

                    <div class="space-y-3 text-sm text-[#9aa3b2] font-mono mb-6">
                        <p>Booking &bull; <span class="text-white">CB-261015-0482</span></p>
                        <p>Movie &bull; <span class="text-white">The Last Horizon &bull; Oct 15 &bull; 19:30</span></p>
                        <p>Payment &bull; <span class="text-white">$24.00 &bull; Failed</span></p>
                    </div>

                    <div class="rounded-lg bg-[#17294f] p-4 text-sm leading-relaxed mb-8">
                        <p class="font-semibold text-blue-300">ⓘ No confirmed booking</p>
                        <p class="text-[#9aa3b2]">G7 and G8 may no longer be available. Start a new seat selection before trying payment again.</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('home') }}" class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6] transition-colors">
                        Choose seats again
                    </a>
                    <a href="{{ route('dashboard') }}" class="rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c] transition-colors">
                        My bookings
                    </a>
                </div>
            </div>

            {{-- 2. HOLD EXPIRED --}}
            <div class="flex flex-col justify-between rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 sm:p-10 shadow-xl">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-6">Hold expired</h2>

                    <div class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#f59e0b] text-2xl text-[#f59e0b] mb-6">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <p class="text-xs font-mono uppercase tracking-wide text-[#9aa3b2] mb-2">! EXPIRED &bull; SEATS RELEASED</p>

                    <h3 class="text-3xl font-bold leading-tight text-white mb-4">Your time ran out.</h3>

                    <p class="text-sm text-[#9aa3b2] leading-relaxed mb-6">
                        The 10-minute hold expired at 18:30:00. No verified payment was received in time, so D7 and D8 were released together.
                    </p>

                    <div class="space-y-3 text-sm text-[#9aa3b2] font-mono mb-6">
                        <p>Booking &bull; <span class="text-white">CB-081011-6682</span></p>
                        <p>Movie &bull; <span class="text-white">The Last Horizon &bull; Screen 1 &bull; 18:30</span></p>
                    </div>

                    <div class="rounded-lg bg-[#17294f] p-4 text-sm leading-relaxed mb-8">
                        <p class="font-semibold text-blue-300">ⓘ No ticket was issued</p>
                        <p class="text-[#9aa3b2]">Choose from the seats currently available. A new hold starts only when you press Hold seats again.</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('home') }}" class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6] transition-colors">
                        Find available seats
                    </a>
                    <a href="{{ route('dashboard') }}" class="rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c] transition-colors">
                        My bookings
                    </a>
                </div>
            </div>

            {{-- 3. LATE PAYMENT • BOOKING FAILED --}}
            <div class="flex flex-col justify-between rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 sm:p-10 shadow-xl">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-6">Late payment &bull; booking failed</h2>

                    <div class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#f43f5e] text-2xl font-bold text-[#f43f5e] mb-6">!</div>

                    <p class="text-xs font-mono uppercase tracking-wide text-[#9aa3b2] mb-2">! FAILED &bull; REFUND PENDING</p>

                    <h3 class="text-3xl font-bold leading-tight text-white mb-4">Paid too late. We're refunding it.</h3>

                    <p class="text-sm text-[#9aa3b2] leading-relaxed mb-6">
                        Payment arrived after the hold expired and D7/D8 were no longer available. Your booking failed. You do not have tickets.
                    </p>

                    <div class="space-y-3 text-sm text-[#9aa3b2] font-mono mb-6">
                        <p>Verified late payment &bull; <span class="text-white">$26.00 (USD - 18:32)</span></p>
                        <p>Automatic full refund &bull; <span class="text-white">$26.00 - Pending</span></p>
                        <p>Booking &bull; <span class="text-white">CB-081011-6682</span></p>
                    </div>

                    <div class="rounded-lg border border-rose-900/50 bg-rose-950/30 p-4 text-sm leading-relaxed text-rose-200 mb-8">
                        <p class="font-semibold">! Not a confirmed reservation</p>
                        <p>The full refund has been requested automatically. You can track it in booking details; refund completion is still pending.</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('dashboard') }}" class="rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c] transition-colors">
                        View refund status
                    </a>
                    <a href="{{ route('home') }}" class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6] transition-colors">
                        Choose a new show
                    </a>
                </div>
            </div>

            {{-- 4. CHECKOUT CLOSED • HOLD STILL ACTIVE --}}
            <div class="flex flex-col justify-between rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 sm:p-10 shadow-xl">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-6">Checkout closed &bull; hold still active</h2>

                    <div class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-sky-500 text-2xl font-bold text-sky-400 mb-6">⏸</div>

                    <p class="text-xs font-mono uppercase tracking-wide text-[#9aa3b2] mb-2">PENDING PAYMENT &bull; NOT CONFIRMED</p>

                    <h3 class="text-3xl font-bold leading-tight text-white mb-4">Your seats are still waiting.</h3>

                    <p class="text-sm text-[#9aa3b2] leading-relaxed mb-6">
                        You cancelled the browser checkout or Stripe. That didn't cancel your hold or confirm your booking.
                    </p>

                    <div class="space-y-3 text-sm text-[#9aa3b2] font-mono mb-6">
                        <p>Seats / label &bull; <span class="text-white">D7, D8 Premium / $26.00 USD</span></p>
                        <p>Time remaining &bull; <span class="text-white">09:18 - Expires at 18:30:00</span></p>
                    </div>

                    <div class="rounded-lg bg-[#17294f] p-4 text-sm leading-relaxed mb-8">
                        <p class="font-semibold text-blue-300">ⓘ Resume while hold is active</p>
                        <p class="text-[#9aa3b2]">Continue the same seat hold checkout, release the hold now, or let it expire. After expiry both seats will be released.</p>
                    </div>
                </div>

                   
<div class="flex flex-wrap gap-3">
    <form method="POST" action="{{ route('payment.checkout', 3) }}">
        @csrf
        <button
            type="submit"
            class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white transition-colors hover:bg-[#2459d6]"
        >
            Resume Stripe Checkout (or retry)
        </button>
    </form>

    
<form
    method="POST"
    action="{{ route('booking.release-hold', 1) }}"
    onsubmit="const b = this.querySelector('button[type=submit]'); b.disabled = true; b.textContent = 'Releasing...';"
>
    @csrf
    <button
        type="submit"
        class="w-full rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white transition-colors hover:bg-[#16223c] disabled:cursor-wait disabled:opacity-60 sm:w-auto"
    >
        Release hold
    </button>
</form>

</div>


            </div>

        </div>
    </div>
    
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(
        'form[action*="/release-hold"]'
    );

    if (!form) return;

    let confirmed = false;

    form.addEventListener('submit', (event) => {
        if (confirmed) return;

        event.preventDefault();

        const modal = document.createElement('div');
        modal.className =
            'fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4';

        modal.innerHTML = `
            <div class="w-full max-w-md rounded-2xl border border-[#263653] bg-[#0a1020] p-6 shadow-2xl sm:p-8">
                <h2 class="mb-3 text-xl font-bold text-white">
                    Release this hold?
                </h2>
                <p class="mb-6 text-sm leading-6 text-[#b8c2d4]">
                    Your booking will be cancelled and the held seats released.
                    This action cannot be undone.
                </p>
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" data-cancel
                        class="rounded-lg border border-[#263653] px-5 py-3 text-white">
                        Keep hold
                    </button>
                    <button type="button" data-confirm
                        class="rounded-lg bg-rose-600 px-5 py-3 font-semibold text-white">
                        Yes, release hold
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelector('[data-cancel]').onclick = () => modal.remove();

        modal.querySelector('[data-confirm]').onclick = () => {
            confirmed = true;
            modal.remove();
            form.requestSubmit();
        };
    });
});
</script>


</x-layouts::site>