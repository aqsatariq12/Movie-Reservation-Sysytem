<x-layouts::staff active="lookup" title="Reference lookup · CineBook Staff">
    <div class="mx-auto flex max-w-[1440px] flex-col gap-6">
        <div>
            <p class="text-xs uppercase">Entry desk · Booking assistance</p>
            <h1 class="mt-4 text-3xl font-bold text-[#f4f7ff] sm:text-[38px]">
                Help a guest find their ticket
            </h1>
            <p class="mt-2 text-sm text-[#aab8cf]">
                Search by the guest's booking reference. This page is read-only.
            </p>
        </div>

        <form method="GET" action="{{ route('ui.staff.lookup') }}"
              class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <label class="flex-1 rounded-lg border-2 border-[#347bff] bg-[#101a2d] px-4 py-3">
                <span class="block text-[10px] uppercase">Booking reference</span>
                <input
                    type="text"
                    name="reference"
                    value="{{ $reference }}"
                    autocomplete="off"
                    required
                    maxlength="100"
                    placeholder="Enter booking reference"
                    class="mt-1 w-full bg-transparent text-sm text-[#f4f7ff] outline-none placeholder:text-[#74849f]"
                >
            </label>

            <button type="submit"
                    class="rounded-lg bg-[#347bff] px-5 py-[13px] text-sm font-semibold text-white hover:brightness-110">
                Look up reference →
            </button>
        </form>

        @if ($reference !== '' && ! $booking)
            <div class="rounded-xl border border-[#5a4526] bg-[#382e20] p-5 text-sm text-[#f4f7ff]">
                <p class="font-semibold">Booking not found</p>
                <p class="mt-2">
                    No booking matches reference
                    <span class="font-semibold">{{ $reference }}</span>.
                    Check the reference and try again.
                </p>
            </div>
        @endif

        @if ($booking)
            @php
                $bookingStatus = $booking->status instanceof \BackedEnum
                    ? $booking->status->value
                    : (string) $booking->status;

                $showtime = $booking->showtime;
                $movie = $showtime?->movie;
                $screen = $showtime?->screen;

                $latestScan = $booking->tickets
                    ->flatMap(fn ($ticket) => $ticket->scans)
                    ->sortByDesc('scanned_at')
                    ->first();

                $statusLabels = [
                    'confirmed' => 'Confirmed booking',
                    'pending' => 'Pending booking',
                    'cancelled' => 'Cancelled booking',
                    'expired' => 'Expired booking',
                ];

                $bookingStatusLabel = $statusLabels[$bookingStatus]
                    ?? \Illuminate\Support\Str::headline($bookingStatus);

                $bookingStatusColor = $bookingStatus === 'confirmed'
                    ? 'text-[#60d4ae]'
                    : 'text-[#f4c46b]';
            @endphp

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <section class="flex flex-col gap-5 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <p class="font-semibold uppercase {{ $bookingStatusColor }}">
                            {{ $bookingStatusLabel }}
                        </p>
                        <p>{{ $booking->booking_reference }}</p>
                    </div>

                    <div>
                        <h2 class="text-3xl font-bold text-[#f4f7ff]">
                            {{ $booking->user?->name ?? 'Guest name unavailable' }}
                        </h2>

                        <p class="mt-3 text-sm leading-6">
                            {{ $movie?->title ?? 'Movie unavailable' }}
                            ·
                            {{ $showtime?->starts_at?->format('M d, Y · H:i') ?? 'Showtime unavailable' }}
                            ·
                            {{ $screen?->name ?? 'Screen unavailable' }}
                        </p>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-[#283750] p-4">
                        <table class="w-full min-w-[520px] text-left text-xs">
                            <thead>
                                <tr class="h-12 uppercase">
                                    <th class="px-3 font-normal text-[#f4f7ff]">Seat</th>
                                    <th class="px-3 font-normal">Ticket</th>
                                    <th class="px-3 font-normal">Ticket status</th>
                                    <th class="px-3 font-normal">Latest scan</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($booking->bookingSeats as $bookingSeat)
                                    @php
                                        $ticket = $booking->tickets->firstWhere(
                                            'booking_seat_id',
                                            $bookingSeat->id
                                        );

                                        $ticketStatus = $ticket
                                            ? ($ticket->status instanceof \BackedEnum
                                                ? $ticket->status->value
                                                : (string) $ticket->status)
                                            : null;

                                        $latestTicketScan = $ticket?->scans
                                            ->sortByDesc('scanned_at')
                                            ->first();

                                        $scanResult = $latestTicketScan?->result;

                                        $scanResultValue = $scanResult instanceof \BackedEnum
                                            ? $scanResult->value
                                            : $scanResult;

                                        $ticketStatusLabel = $ticketStatus
                                            ? \Illuminate\Support\Str::headline($ticketStatus)
                                            : 'Ticket not issued';

                                        $scanLabel = $latestTicketScan
                                            ? \Illuminate\Support\Str::headline((string) $scanResultValue)
                                                . ' · '
                                                . $latestTicketScan->scanned_at?->format('M d, H:i')
                                            : 'Not scanned';
                                    @endphp

                                    <tr class="h-[60px] border-t border-[#283750]">
                                        <td class="px-3 text-[#f4f7ff]">
                                            <span>{{ $bookingSeat->seat?->label ?? 'Seat unavailable' }}</span>

@if ($bookingSeat->seat?->type)
    <span class="block mt-1 text-[10px] text-[#aab8cf]">
                                                    {{ $bookingSeat->seat->type instanceof \BackedEnum
                                                        ? \Illuminate\Support\Str::headline($bookingSeat->seat->type->value)
                                                        : \Illuminate\Support\Str::headline((string) $bookingSeat->seat->type) }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-3">
                                            {{ $ticket ? 'T-' . str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT) : '—' }}
                                        </td>

                                        <td class="px-3">
                                            {{ $ticketStatusLabel }}
                                            @if ($ticket?->used_at)
                                                <span class="block mt-1 text-[10px]">
                                                    {{ $ticket->used_at->format('M d, H:i') }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-3">{{ $scanLabel }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center">
                                            No seats are linked to this booking.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
                        <p class="font-semibold">ⓘ Damaged QR assistance</p>
                        <p class="mt-2">
                            Use the ticket details to help the guest locate their ticket.
                            This lookup does not admit a guest or change any ticket status.
                        </p>
                    </div>

                    <div>
                        <a href="{{ route('ui.staff.scanner') }}"
                           class="inline-block rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm font-semibold text-[#f4f7ff] hover:brightness-125">
                            Return to scanner →
                        </a>
                    </div>
                </section>

                <aside class="flex flex-col gap-4">
                    <div class="flex flex-col gap-4 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6 text-sm">
                        <h3 class="text-2xl font-bold text-[#f4f7ff]">Admission context</h3>

                        <p>
                            Showtime ·
                            {{ $showtime?->starts_at?->format('H:i') ?? 'Unavailable' }}
                        </p>

                        <p>Screen · {{ $screen?->name ?? 'Unavailable' }}</p>

                        <p>Seats · {{ $booking->bookingSeats->count() }}</p>

                        <p>Tickets issued · {{ $booking->tickets->count() }}</p>

                        <p>
                            Booking created ·
                            {{ $booking->created_at?->format('M d, Y · H:i') ?? 'Unavailable' }}
                        </p>

                        <p class="text-xs leading-[1.6]">
                            Read-only result. No payment, refund or email details are shown.
                        </p>
                    </div>

                    <div class="rounded-[10px] border border-[#5a4526] bg-[#382e20] p-4 text-xs leading-[1.6]">
                        <p class="font-semibold text-[#f4f7ff]">ⓘ Single-use rules still apply</p>
                        <p class="mt-2">
                            Reference lookup does not override ticket validation.
                            Admission must still be checked by the scanner.
                        </p>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</x-layouts::staff>