{{-- resources/views/bookings/index.blade.php --}}
@php
    $money = fn(int $cents, string $currency = 'USD') => '$' . number_format($cents / 100, $cents % 100 === 0 ? 0 : 2);
    $moneyFull = fn(int $cents) => '$' . number_format($cents / 100, 2);

    $seatLabels = fn($booking) => $booking->bookingSeats
        ->map(fn($bs) => $bs->seat->row_label . $bs->seat->seat_number)
        ->join(', ');

    $statusLabel = function ($booking) {
        $status = $booking->status instanceof \BackedEnum
            ? $booking->status->value
            : (string) $booking->status;

        $labels = [
            'confirmed' => 'Confirmed',
            'pending_payment' => 'Pending payment',
            'failed' => 'Failed',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ];

        $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));

        $hasPendingRefund = $booking->payments
            ->flatMap(fn($payment) => $payment->refunds)
            ->contains(fn($refund) => $refund->status instanceof \BackedEnum
                ? $refund->status->value === 'pending'
                : $refund->status === 'pending');

        if ($status === 'cancelled' && $hasPendingRefund) {
            $label .= ' · refund pending';
        }

        return $label;
    };

    // $accessLink = function ($booking) {
    //     if ($booking->status === 'confirmed') {
    //         return ['Tickets →', route('bookings.index', $booking)];
    //     }
    //     if ($booking->status === 'pending_payment') {
    //         // Route is owned by Member B (checkout). Falls back to details until it exists.
    //         return [
    //             'Resume →',
    //             \Illuminate\Support\Facades\Route::has('checkout.show')
    //             ? route('checkout.show', $booking)
    //             : route('bookings.index', $booking)
    //         ];
    //     }
    //     return ['Details →', route('bookings.index', $booking)];
    // };

    $accessLink = function ($booking) {
        $status = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;

        if ($status === 'confirmed') {
            return ['Tickets →', route('bookings.show', $booking) . '#tickets'];
        }
        if ($status === 'pending_payment') {
            return [
                'Resume →',
                \Illuminate\Support\Facades\Route::has('checkout.show')
                ? route('checkout.show', $booking)
                : route('bookings.show', $booking),
            ];
        }
        return ['Details →', route('bookings.show', $booking)];
    };
    $tabs = [
        'upcoming' => ['Upcoming', $counts['upcoming']],
        'past' => ['Past', $counts['past']],
        'all' => ['All', $counts['all']],
    ];
@endphp

<x-layouts.cinebook title="My Bookings">
    <div class="bg-[#f7f4ee]">
        <div class="mx-auto flex max-w-[1312px] flex-col gap-7 px-6 py-10 sm:px-16">

            {{-- Header --}}
            <div>
                <p class="text-sm uppercase leading-[1.6] text-[#6b7280]">Account / My bookings</p>
                <h1 class="mt-3 text-[38px] font-bold leading-[1.2] text-[#1f2937]">Your movie nights</h1>

                <nav class="mt-3 flex items-center gap-8 text-sm leading-[1.6]" aria-label="Booking filters">
                    @foreach ($tabs as $key => [$label, $count])
                        <a href="{{ route('bookings.index', ['filter' => $key]) }}"
                            class="{{ $filter === $key ? 'text-[#347bff] underline underline-offset-4' : 'text-[#6b7280] hover:text-[#1f2937]' }}">
                            {{ $label }} · {{ $count }}
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Featured confirmed booking --}}
            @if ($featured)
                @php
                    $movie = $featured->showtime->movie;
                    $types = $featured->bookingSeats
                        ->pluck('seat.type')
                        ->map(fn($type) => $type instanceof \BackedEnum ? $type->value : (string) $type)
                        ->unique();

                    $seatCount = $featured->bookingSeats->count();

                    $typeText = $types->count() === 1
                        ? $seatCount . ' ' . ucfirst($types->first()) . ' ' . \Illuminate\Support\Str::plural('seat', $seatCount)
                        : $seatCount . ' ' . \Illuminate\Support\Str::plural('seat', $seatCount);
                @endphp
                <section class="rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                    <div class="flex flex-col gap-7 sm:flex-row sm:items-center">
                        <div
                            class="h-[200px] w-[150px] shrink-0 overflow-hidden rounded-[10px] bg-gradient-to-br from-[#1f2937] to-[#347bff]">
                            @if ($movie->poster_path)
                                <img src="{{ asset('/' . $movie->poster_path) }}" alt="{{ $movie->title }} poster"
                                    class="h-full w-full object-cover">
                            @endif
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col gap-4">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-xs text-[#60d4ae]">✓ Confirmed</span>
                                <span class="text-[13px] text-[#6b7280]">{{ $featured->booking_reference }}</span>
                            </div>

                            <div>
                                <h2 class="text-[32px] font-bold leading-[1.2] text-[#1f2937]">{{ $movie->title }}</h2>
                                <p class="mt-4 text-sm leading-[1.6] text-[#6b7280]">
                                    {{ $featured->showtime->starts_at->format('D, M j · H:i') }}
                                    · {{ $featured->showtime->screen->name }} · CineBook Downtown
                                </p>
                                <p class="mt-4 text-sm leading-[1.6] text-[#6b7280]">
                                    {{ $seatLabels($featured) }} · {{ $typeText }}
                                    · {{ $moneyFull($featured->total_amount_cents) }} {{ $featured->currency }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-4">
                                <a href="{{ route('bookings.show', $featured) . '#tickets' }}"
                                    class="rounded-xl border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#2a68dc]">
                                    View tickets →
                                </a>

                                <a href="{{ route('bookings.show', $featured) }}"
                                    class="rounded-xl border border-[#e3d8c8] bg-white px-5 py-[13px] text-sm text-[#1f2937] hover:bg-[#f7f4ee]">
                                    Booking details
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Pending payment bookings with an active hold --}}
            @foreach ($pending as $booking)
                <section
                    class="flex flex-col gap-5 rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                    <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-3">
                        <span class="text-xs text-[#925812]">◷ Pending payment</span>
                        <h2 class="text-[22px] font-bold leading-[1.13] text-[#1f2937] sm:text-center">
                            {{ $booking->showtime->movie->title }}
                        </h2>
                        <span class="text-[13px] text-[#6b7280] sm:text-right">{{ $booking->booking_reference }}</span>
                    </div>

                    <p class="text-base leading-[1.45] text-[#6b7280]">
                        {{ $booking->showtime->starts_at->format('D, M j · H:i') }}
                        · {{ $booking->showtime->screen->name }}
                        · {{ $seatLabels($booking) }}
                        · {{ $money($booking->total_amount_cents) }}
                    </p>

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm leading-[1.45] text-[#925812]">
                            Hold expires
                            {{ $booking->hold->expires_at->isToday() ? 'today at' : 'on ' . $booking->hold->expires_at->format('M j') . ' at' }}
                            {{ $booking->hold->expires_at->format('H:i:s') }}
                            ·
                            <span data-countdown="{{ $booking->hold->expires_at->toIso8601String() }}">--:--</span>
                            remaining
                        </p>
                        <a href="{{ $accessLink($booking)[1] }}"
                            class="rounded-xl border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-center text-sm text-white hover:bg-[#2a68dc]">
                            Resume payment
                        </a>
                    </div>
                </section>
            @endforeach

            {{-- All booking activity --}}
            <h2 class="text-2xl font-bold leading-[1.13] text-[#1f2937]">All booking activity</h2>

            <div
                class="overflow-x-auto rounded-xl border border-[#e3d8c8] bg-white p-4 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                <table class="w-full min-w-[900px] text-left text-xs">
                    <thead>
                        <tr class="h-[58px] uppercase">
                            <th class="px-3 font-normal text-[#1f2937]">Reference / Movie</th>
                            <th class="px-3 font-normal text-[#6b7280]">Showtime</th>
                            <th class="px-3 font-normal text-[#6b7280]">Seats / Total</th>
                            <th class="px-3 font-normal text-[#347bff]">Status</th>
                            <th class="px-3 font-normal text-[#6b7280]">Access</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            @php [$accessText, $accessUrl] = $accessLink($booking); @endphp
                            <tr class="h-[58px]">
                                <td class="px-3 text-[#1f2937]">
                                    {{ $booking->booking_reference }} / {{ $booking->showtime->movie->title }}
                                </td>
                                <td class="px-3 text-[#6b7280]">
                                    {{ $booking->showtime->starts_at->format('M j · H:i') }}
                                </td>
                                <td class="px-3 text-[#6b7280]">
                                    {{ $seatLabels($booking) }} / {{ $money($booking->total_amount_cents) }}
                                </td>
                                <td class="px-3 text-[#347bff]">{{ $statusLabel($booking) }}</td>
                                <td class="px-3 text-[#6b7280]">
                                    <a href="{{ $accessUrl }}" class="hover:text-[#347bff]">{{ $accessText }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-10 text-center text-sm text-[#6b7280]">
                                    No bookings yet.
                                    <a href="{{ route('movies.index') }}" class="text-[#347bff] underline">Browse movies</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-xs leading-[1.45] text-[#6b7280]">
                Only your bookings are shown. A pending payment is not a ticket.
            </p>
        </div>
    </div>

    <script>
        // Live countdown for pending-payment holds (display only; the server decides expiry).
        document.querySelectorAll('[data-countdown]').forEach((el) => {
            const end = new Date(el.dataset.countdown).getTime();
            const tick = () => {
                const s = Math.max(0, Math.floor((end - Date.now()) / 1000));
                el.textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
                if (s === 0) clearInterval(timer);
            };
            const timer = setInterval(tick, 1000);
            tick();
        });
    </script>
</x-layouts.cinebook>