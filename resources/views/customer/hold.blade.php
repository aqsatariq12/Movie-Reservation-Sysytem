@php
    $money = fn(int $c) => \App\Models\Showtime::money($c);
    $labels = $seats->pluck('label')->implode(' + ');
    $types = $seats->pluck('type')->unique()->implode(' / ');
    $count = $seats->count();
@endphp

<x-layouts.cinebook :title="__('Your seats are on hold')">
    <section class="mx-auto w-full max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 lg:py-10">

        {{-- Page heading --}}
        <div class="mb-8 flex flex-col gap-3">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cb-ink/60">
                01 Select seats ✓ → 02 Checkout → 03 Your tickets
            </p>

            <h1 class="text-3xl font-bold leading-tight tracking-tight text-cb-ink md:text-4xl">
                Your seats are on hold
            </h1>

            <p class="max-w-2xl text-sm leading-6 text-cb-ink/65">
                Your seats are temporarily reserved. Complete checkout before the timer expires.
            </p>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_390px] xl:gap-8">

            {{-- LEFT: Movie information and order details --}}
            <div class="flex min-w-0 flex-col gap-6">

                {{-- Movie and booking summary --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-5 sm:flex-row">
                        <div class="flex h-[150px] w-[105px] shrink-0 items-center justify-center overflow-hidden rounded-xl bg-blue-50 text-3xl font-bold text-blue-700 sm:h-[175px] sm:w-[120px]">
                            @if ($movie->poster_url)
                                <img
                                    src="{{ $movie->poster_url }}"
                                    alt="{{ $movie->title }} poster"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                {{ \Illuminate\Support\Str::of($movie->title)->substr(0, 1) }}
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">
                                CineBook Downtown · {{ $screen->name }}
                            </p>

                            <h2 class="mt-2 break-words text-2xl font-bold leading-tight text-cb-ink sm:text-3xl">
                                {{ $movie->title }}
                            </h2>

                            <p class="mt-3 text-sm leading-6 text-cb-ink/65">
                                {{ $showtime->starts_at->format('D, F j, Y · H:i') }}
                            </p>

                            <p class="text-sm leading-6 text-cb-ink/65">
                                {{ $movie->duration_minutes }} min · {{ $movie->rating }}
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($seats as $seat)
                                    <span class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-800">
                                        {{ $seat['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-cb-ink/50">
                                Booking reference
                            </p>
                            <p class="mt-1 break-all font-semibold text-cb-ink">
                                {{ $booking->booking_reference }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-cb-ink/50">
                                Seats selected
                            </p>
                            <p class="mt-1 font-semibold text-cb-ink">
                                {{ $count }} {{ \Illuminate\Support\Str::plural('seat', $count) }}
                                <span class="font-normal text-cb-ink/55">· {{ $types }}</span>
                            </p>
                        </div>
                    </div>
                </section>

                {{-- Order details --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-xl font-bold text-cb-ink sm:text-2xl">
                            Order details
                        </h2>

                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-cb-ink/65">
                            {{ $count }} {{ \Illuminate\Support\Str::plural('seat', $count) }}
                        </span>
                    </div>

                    <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full min-w-[440px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-cb-ink/60">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Seat</th>
                                    <th class="px-4 py-3 font-semibold">Type</th>
                                    <th class="px-4 py-3 text-right font-semibold">Unit price</th>
                                    <th class="px-4 py-3 text-right font-semibold">Subtotal</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @foreach ($seats as $seat)
                                    <tr>
                                        <td class="px-4 py-4 font-semibold text-cb-ink">
                                            {{ $seat['label'] }}
                                        </td>
                                        <td class="px-4 py-4 text-cb-ink/65">
                                            {{ $seat['type'] }}
                                        </td>
                                        <td class="px-4 py-4 text-right text-cb-ink/65">
                                            {{ $money($seat['price']) }}
                                        </td>
                                        <td class="px-4 py-4 text-right font-medium text-cb-ink">
                                            {{ $money($seat['price']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot class="border-t border-slate-200 bg-slate-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-right font-semibold text-cb-ink">
                                        Total ({{ $booking->currency }})
                                    </td>
                                    <td class="px-4 py-4 text-right text-lg font-bold text-cb-ink">
                                        {{ $money($booking->total_amount_cents) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm leading-6 text-cb-ink/65">
                        <p>
                            Standard seat: {{ $money($showtime->base_price_cents) }}.
                            Premium seats include a {{ $money($surcharge) }} surcharge.
                        </p>
                        <p class="mt-1">No additional fees.</p>
                    </div>
                </section>

                {{-- Signed-in customer --}}
                <section class="rounded-xl border border-blue-100 bg-blue-50 p-4 sm:p-5">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-blue-700">
                            ✓
                        </span>

                        <div class="min-w-0">
                            <p class="font-semibold text-blue-900">Verified customer</p>
                            <p class="mt-1 break-words text-sm text-blue-900/75">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="break-all text-sm text-blue-900/75">
                                {{ auth()->user()->email }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            {{-- RIGHT: Sticky checkout panel --}}
            <aside class="flex min-w-0 flex-col gap-5 xl:sticky xl:top-6">

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cb-ink/60">
                            Hold active
                        </p>

                        <span class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700">
                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                            Temporary hold
                        </span>
                    </div>

                    <div class="mt-5 rounded-xl bg-blue-50 px-4 py-5 text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-blue-800/70">
                            Time remaining
                        </p>

                        <p id="countdown" role="timer" aria-live="off"
                           class="mt-2 text-5xl font-bold tabular-nums tracking-tight text-blue-700 sm:text-6xl">
                            --:--
                        </p>

                        <p id="countdown-note" class="mt-3 text-xs leading-5 text-blue-900/70">
                            Your {{ \App\Models\Setting::readInt('hold_minutes', 10) }}-minute hold expires
                            <span id="expires-text"></span>
                        </p>
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-3">
                        <span class="text-sm text-cb-ink/65">Total due</span>
                        <span class="text-xl font-bold text-cb-ink">
                            {{ $booking->currency }} {{ $money($booking->total_amount_cents) }}
                        </span>
                    </div>

                    <div class="my-5 border-t border-slate-100"></div>

                    {{-- Stripe checkout --}}
                    @if (Route::has('bookings.checkout'))
                        <form method="POST" action="{{ route('bookings.checkout', $booking) }}">
                            @csrf
                            <button
                                id="continue-button"
                                type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500"
                            >
                                Continue to Stripe <span aria-hidden="true">→</span>
                            </button>
                        </form>
                    @else
                        <button
                            id="continue-button"
                            type="button"
                            disabled
                            class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-slate-200 px-5 py-3.5 text-sm font-semibold text-slate-500"
                        >
                            Checkout unavailable
                        </button>

                        <p class="mt-2 text-xs leading-5 text-amber-700">
                            Online checkout is not available yet.
                        </p>
                    @endif

                    <p class="mt-3 text-xs leading-5 text-cb-ink/55">
                        Payment takes place on Stripe's hosted Checkout. Test mode is intended for simulated payments.
                    </p>

                    <form class="mt-4" method="POST" action="{{ route('holds.destroy', $hold) }}"
                          onsubmit="return confirm('Release these seats and return to seat selection?');">
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-cb-ink transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                        >
                            Release hold &amp; choose seats
                        </button>
                    </form>
                </section>

                {{-- Expiry reminder --}}
                <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    <p class="font-semibold">Keep an eye on the clock</p>
                    <p class="mt-1 text-amber-900/80">
                        Your booking is confirmed only after verified payment. If the hold expires, all held seats are released.
                    </p>
                </section>

                <p class="px-1 text-xs leading-5 text-cb-ink/60">
                    Cancellation is available until {{ $cancelUntil->format('H:i') }},
                    provided no ticket has been scanned.
                </p>
            </aside>
        </div>
    </section>

    <script>
        (function () {
            const expiresAt = new Date(@json($hold->expires_at->toIso8601String())).getTime();

            // Use the server's timestamp as the reference for the countdown.
            const serverNow = new Date(@json($now->toIso8601String())).getTime();
            const offset = serverNow - Date.now();

            const pad = (n) => String(n).padStart(2, '0');
            const clock = document.getElementById('countdown');
            const continueButton = document.getElementById('continue-button');
            const countdownNote = document.getElementById('countdown-note');
            const expiresText = document.getElementById('expires-text');

            expiresText.textContent = new Date(expiresAt).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            }) + ' local time.';

            let timer;

            function tick() {
                const remaining = Math.max(
                    0,
                    Math.floor((expiresAt - (Date.now() + offset)) / 1000)
                );

                clock.textContent =
                    pad(Math.floor(remaining / 60)) + ':' + pad(remaining % 60);

                if (remaining <= 0) {
                    clearInterval(timer);
                    clock.textContent = '00:00';
                    countdownNote.textContent = 'Your hold has expired. Reloading…';

                    if (continueButton) {
                        continueButton.disabled = true;
                    }

                    // The server remains responsible for validating the hold.
                    setTimeout(() => window.location.reload(), 1500);
                }
            }

            tick();
            timer = setInterval(tick, 1000);
        })();
    </script>
</x-layouts.cinebook>
