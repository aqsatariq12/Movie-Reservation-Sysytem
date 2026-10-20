@php
    $money = fn (int $c) => \App\Models\Showtime::money($c);
    $labels = $seats->pluck('label')->implode(' + ');
    $types = $seats->pluck('type')->unique()->implode(' / ');
    $count = $seats->count();
@endphp

<x-layouts::customer :title="__('Your seats are on hold')">
    <p class="text-xs uppercase tracking-wide text-[#a0aec5]">01 Select seats ✓ → 02 Checkout → 03 Your tickets</p>
    <h1 class="text-[40px] font-bold leading-[1.15]">Your seats are on hold</h1>

    <div class="flex flex-col gap-6 xl:flex-row xl:items-start">
        {{-- LEFT --}}
        <div class="flex min-w-0 flex-1 flex-col gap-4">
            <div class="flex gap-5 rounded-2xl border border-[#283750] bg-[#101a2d] p-5">
                <div class="flex h-[128px] w-[75px] shrink-0 items-center justify-center rounded-lg bg-gradient-to-b from-[#1b2a45] to-[#0c1424] text-2xl text-[#87beff]">
                    {{ \Illuminate\Support\Str::of($movie->title)->substr(0, 1) }}
                </div>
                <div class="min-w-0 text-[13px] leading-[1.6] text-[#a0aec5]">
                    <p class="text-[22px] font-bold leading-[1.3] text-[#f4f7ff]">{{ $movie->title }}</p>
                    <p>{{ $showtime->starts_at->format('D, F j, Y · H:i') }}</p>
                    <p>CineBook Downtown · {{ $screen->name }}</p>
                    <p>{{ $movie->duration_minutes }} min · {{ $movie->rating }}</p>
                    <p>{{ $labels }} · {{ $types }}</p>
                    <p>{{ $booking->booking_reference }}</p>
                    <p>Total · {{ $money($booking->total_amount_cents) }} {{ $booking->currency }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
                <h2 class="text-[22px] font-bold">Order details</h2>

                <div class="mt-4 overflow-x-auto rounded-lg border border-[#283750] p-4">
                    <table class="w-full min-w-[420px] text-left text-[13px]">
                        <thead class="uppercase text-[#a0aec5]">
                            <tr>
                                <th class="pb-4 font-normal">Seat</th>
                                <th class="pb-4 font-normal">Type</th>
                                <th class="pb-4 font-normal">Unit price</th>
                                <th class="pb-4 font-normal">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="text-[#a0aec5]">
                            @foreach ($seats as $seat)
                                <tr>
                                    <td class="py-3 text-[#f4f7ff]">{{ $seat['label'] }}</td>
                                    <td class="py-3">{{ $seat['type'] }}</td>
                                    <td class="py-3">{{ $money($seat['price']) }}</td>
                                    <td class="py-3">{{ $money($seat['price']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-4 text-[13px] text-[#a0aec5]">
                    Each Premium seat: {{ $money($showtime->base_price_cents) }} Standard + {{ $money($surcharge) }} surcharge. No additional fees.
                </p>
                <p class="mt-4 text-[13px] text-[#a0aec5]">{{ $count }} {{ \Illuminate\Support\Str::plural('seat', $count) }} · {{ $booking->currency }} · {{ $money($booking->total_amount_cents) }}</p>
            </div>

            <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6]">
                <p>ⓘ Verified customer</p>
                <p>{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
            </div>
        </div>

        {{-- RIGHT --}}
        <div class="flex w-full shrink-0 flex-col gap-4 xl:w-[390px] xl:pt-10">
            <div class="flex flex-col gap-5 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6">
                <p class="text-xs uppercase text-[#a0aec5]">◷ Hold active</p>
                <p id="countdown" class="text-[56px] font-bold leading-none">--:--</p>
                <p id="countdown-note" class="text-[13px] leading-[1.6] text-[#a0aec5]">
                    Remaining on your {{ \App\Models\Showtime::setting('hold_minutes', 10) }}-minute hold <span id="expires-text"></span>
                </p>
                <p class="text-[13px] text-[#a0aec5]">Total due · {{ $booking->currency }} · {{ $money($booking->total_amount_cents) }}</p>

                {{-- The Stripe step belongs to Member B. Agreed name: POST bookings.checkout (booking). --}}
                @if (Route::has('bookings.checkout'))
                    <form method="POST" action="{{ route('bookings.checkout', $booking) }}">
                        @csrf
                        <button id="continue-button" type="submit"
                                class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">
                            Continue to Stripe (test mode) →
                        </button>
                    </form>
                @else
                    <button id="continue-button" type="button" disabled
                            class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-[#74849f]">
                        Continue to Stripe (test mode) →
                    </button>
                @endif

                <p class="text-xs leading-[1.5] text-[#a0aec5]">
                    You’ll pay on Stripe’s hosted Checkout. This is a simulated payment; no real money is charged.
                </p>

                <form method="POST" action="{{ route('holds.destroy', $hold) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:bg-[#1e2f4d]">
                        Release hold &amp; choose seats
                    </button>
                </form>
            </div>

            <div class="rounded-lg bg-[#3a2d1d] p-4 text-sm leading-[1.6] text-[#f0c47a]">
                <p>ⓘ Keep an eye on the clock</p>
                <p>Your booking is confirmed only after we receive verified payment. If the hold expires, all held seats are released.</p>
            </div>

            <p class="text-xs leading-[1.5] text-[#a0aec5]">
                Cancellation for this show is available until {{ $cancelUntil->format('H:i') }}, provided no ticket has been scanned.
            </p>
        </div>
    </div>

    <script>
        (function () {
            const expiresAt = new Date(@json($hold->expires_at->toIso8601String())).getTime();
            // use the server's clock, so a wrong clock on the customer's computer cannot extend the hold
            const offset = new Date(@json($now->toIso8601String())).getTime() - Date.now();
            const pad = (n) => String(n).padStart(2, '0');
            const clock = document.getElementById('countdown');

            document.getElementById('expires-text').textContent =
                'Expires today at ' + new Date(expiresAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' local time.';

            const timer = setInterval(tick, 1000);
            tick();

            function tick() {
                const remaining = Math.max(0, Math.floor((expiresAt - (Date.now() + offset)) / 1000));
                clock.textContent = pad(Math.floor(remaining / 60)) + ':' + pad(remaining % 60);

                if (remaining === 0) {
                    clearInterval(timer);
                    document.getElementById('countdown-note').textContent = 'Your hold has expired. Reloading…';
                    document.getElementById('continue-button').disabled = true;
                    // the server decides: an expired hold redirects back to the seat map
                    setTimeout(() => location.reload(), 1500);
                }
            }
        })();
    </script>
</x-layouts::customer>
