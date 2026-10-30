{{-- resources/views/bookings/show.blade.php --}}
@php
    $status = $booking->status instanceof \BackedEnum
        ? $booking->status->value
        : (string) $booking->status;

    $statusStyle = [
        'confirmed' => ['✓', 'text-[#60d4ae]'],
        'pending_payment' => ['◷', 'text-[#925812]'],
        'cancelled' => ['•', 'text-[#6b7280]'],
        'refunded' => ['•', 'text-[#6b7280]'],
        'failed' => ['•', 'text-[#6b7280]'],
        'expired' => ['•', 'text-[#6b7280]'],
    ][$status] ?? ['•', 'text-[#6b7280]'];

    $routes = \Illuminate\Support\Facades\Route::class;

    $resumeUrl = $routes::has('checkout.show')
        ? route('checkout.show', $booking)
        : route('bookings.show', $booking);
@endphp

<x-layouts.cinebook title="Booking details">
    <div class="bg-[#f7f4ee]">
        <div class="mx-auto flex max-w-[1312px] flex-col gap-7 px-6 py-10 sm:px-16">

            {{-- Header --}}
            <div>
                <p class="text-sm uppercase leading-[1.6] text-[#6b7280]">
                    <a href="{{ route('bookings.index') }}" class="hover:text-[#347bff]">My bookings</a>
                    / {{ $booking->booking_reference }}
                </p>
                <h1 class="mt-3 text-[38px] font-bold leading-[1.2] text-[#1f2937]">Your booking, all in one place</h1>
            </div>

            {{-- Status row --}}
            <div class="flex flex-wrap items-center gap-4">
                <p class="min-w-[140px] flex-1 text-xs {{ $statusStyle[1] }}">{{ $statusStyle[0] }} {{ $statusLabel }}
                </p>

                <p class="min-w-[240px] flex-1 text-[13px] leading-[1.45] text-[#6b7280]">
                    Reserved {{ $booking->created_at->format('F j, Y \a\t H:i') }} · {{ $booking->user->name }}
                </p>

                @if ($status === 'confirmed')
                    <a href="{{ route('booking.tickets', $booking) }}"
                        class="rounded-xl border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#2a68dc] print:hidden">
                        View tickets →
                    </a>
                @endif

                {{-- Temporarily disabled Resume Payment button
                @if ($status === 'pending_payment')
                <a href="{{ $resumeUrl }}"
                    class="rounded-xl border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#2a68dc] print:hidden">
                    Resume payment
                </a>
                @endif
                --}}

                <button type="button" onclick="window.print()"
                    class="rounded-xl border border-[#e3d8c8] bg-white px-5 py-[13px] text-sm text-[#1f2937] hover:bg-[#f7f4ee] print:hidden">
                    Print / Save as PDF
                </button>
            </div>

            <div class="flex flex-col gap-8 lg:flex-row lg:items-center">

                {{-- LEFT COLUMN --}}
                <div class="flex min-w-0 flex-1 flex-col gap-4">

                    {{-- Reservation summary --}}
                    <section
                        class="flex gap-5 rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                        <div
                            class="h-[180px] w-[104px] shrink-0 overflow-hidden rounded-[10px] bg-gradient-to-br from-[#1f2937] to-[#347bff]">
                            @if ($movie->poster_url)
                                <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }} poster"
                                    class="h-full w-full object-cover">
                            @endif
                        </div>

                        <div class="min-w-0 flex-1 text-sm leading-[1.8] text-[#6b7280]">
                            <h2 class="text-2xl font-bold leading-[1.8] text-[#1f2937]">{{ $movie->title }}</h2>
                            <p>{{ $showtime->starts_at->format('D, F j, Y · H:i') }}</p>
                            <p>CineBook Downtown · {{ $showtime->screen->name }}</p>
                            <p>
                                {{ $movie->duration_minutes }} min
                                @if ($movie->rating)
                                    · {{ $movie->rating }}
                                @endif
                            </p>
                            <p>{{ $seatSummary }} · {{ $typeLabel }}</p>
                            <p>{{ $booking->booking_reference }}</p>
                            <p>Total · {{ $total }} {{ $booking->currency }}</p>
                        </div>
                    </section>

                    {{-- Seats & tickets --}}
                    <section id="tickets"
                        class="flex scroll-mt-6 flex-col gap-5 rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                        <h2 class="text-2xl font-bold leading-[1.13] text-[#1f2937]">Your seats &amp; tickets</h2>

                        <div class="overflow-x-auto rounded-xl border border-[#e3d8c8] bg-white p-4">
                            <table class="w-full min-w-[420px] text-left text-xs">
                                <thead>
                                    <tr class="h-[58px] uppercase">
                                        <th class="px-3 font-normal text-[#1f2937]">Seat</th>
                                        <th class="px-3 font-normal text-[#6b7280]">Ticket number</th>
                                        <th class="px-3 font-normal text-[#6b7280]">Scan status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ticketRows as $row)
                                        <tr class="h-[58px]">
                                            <td class="px-3 text-[#1f2937]">{{ $row['seat'] }}</td>
                                            <td class="px-3 text-[#6b7280]">{{ $row['number'] }}</td>
                                            <td class="px-3 text-[#6b7280]">{{ $row['scan'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <p class="text-[13px] leading-[1.45] text-[#6b7280]">
                            @if ($status === 'confirmed')
                                Entry window: {{ $entryWindow }}. Each QR is single-use.
                            @else
                                No active tickets for this booking. A pending payment is not a ticket.
                            @endif
                        </p>
                    </section>

                    {{-- Payment --}}
                    <section
                        class="rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                        <h2 class="text-2xl font-bold leading-[1.2] text-[#1f2937]">Payment</h2>

                        <div class="mt-4 flex flex-col gap-4 text-sm leading-[1.6] text-[#6b7280]">
                            <p>{{ $paymentLine }}</p>
                            <p>Paid at &nbsp;·&nbsp; {{ $paidAt }}</p>
                            <p>Payment reference &nbsp;·&nbsp; {{ $paymentReference }}</p>
                            <p>
                                Refunds &nbsp;·&nbsp;
                                @if ($refundLines->isEmpty())
                                    None
                                @else
                                    {{ $refundLines->join(', ') }}
                                @endif
                            </p>
                        </div>
                    </section>
                </div>

                {{-- RIGHT COLUMN --}}
                <div class="flex w-full shrink-0 flex-col gap-4 lg:w-[400px]">

                    {{-- Timeline --}}
                    <section
                        class="rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                        <h2 class="text-2xl font-bold leading-[1.2] text-[#1f2937]">Booking timeline</h2>

                        <ul class="mt-4 text-sm leading-[1.6]">
                            @foreach ($timeline as $event)
                                <li class="{{ $loop->first ? 'text-[#347bff]' : 'text-[#6b7280]' }}">
                                    •&nbsp; {{ $event['time'] }} · {{ $event['text'] }}
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    {{-- Cancellation --}}
                    @if ($cancellation)
                        <section
                            class="flex flex-col items-start gap-5 rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                            <div class="text-sm leading-[1.6] text-[#6b7280]">
                                <h2 class="text-2xl font-bold leading-[1.2] text-[#1f2937]">Cancellation</h2>

                                @if ($cancellation['state'] === 'eligible')
                                    <p class="mt-4">
                                        Full cancellation is available until {{ $cancellation['deadline'] }} —
                                        {{ $cancellation['hours'] }}
                                        {{ \Illuminate\Support\Str::plural('hour', $cancellation['hours']) }}
                                        before the show — if no ticket has been scanned.
                                    </p>
                                    <p class="mt-4">Eligible now · Full refund {{ $cancellation['refund'] }}</p>
                                @elseif ($cancellation['state'] === 'closed')
                                    <p class="mt-4">
                                        @if ($cancellation['reason'] === 'scanned')
                                            Cancellation is closed because a ticket for this booking has already been scanned.
                                        @else
                                            Cancellation is closed. The {{ $cancellation['hours'] }}-hour cut-off before the
                                            show ({{ $cancellation['deadline'] }}) has passed.
                                        @endif
                                    </p>
                                    <p class="mt-4">Not eligible for cancellation or refund.</p>
                                @else
                                    <p class="mt-4">This booking has been cancelled and its tickets are void.</p>
                                    <p class="mt-4">
                                        Refunds &nbsp;·&nbsp;
                                        @if ($refundLines->isEmpty())
                                            None
                                        @else
                                            {{ $refundLines->join(', ') }}
                                        @endif
                                    </p>
                                @endif
                            </div>

                            @if ($cancellation['state'] === 'eligible')
                                <a href="{{ route('bookings.cancel.confirm', $booking) }}"
                                    class="rounded-xl border border-[#e3d8c8] bg-[#f4f7ff] px-5 py-[13px] text-sm text-[#1f2937] hover:bg-[#e8efff] print:hidden">
                                    Cancel full booking
                                </a>
                            @endif
                        </section>
                    @endif

                    {{-- Email notice --}}
                    @if ($emailPending)
                        <div
                            class="rounded-xl border border-[#e3d8c8] bg-[#f4f7ff] p-4 text-sm leading-[1.6] text-[#1f2937]">
                            <p>ⓘ Email delivery pending</p>
                            <p>Your booking stays confirmed. Tickets are available here regardless of email delivery.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.cinebook>