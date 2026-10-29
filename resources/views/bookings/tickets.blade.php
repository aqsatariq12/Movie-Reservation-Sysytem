@php
    $b = $booking ?? null;
    $start = \Illuminate\Support\Carbon::parse(data_get($b, 'showtime.starts_at'));
    $movie = data_get($b, 'showtime.movie.title');
    $cinema = data_get($b, 'showtime.screen.name');
    $ref = data_get($b, 'booking_reference');
    $guest = auth()->user()->name ?? 'Guest';
    $list = $tickets ?? [];
    $n = count($list);
@endphp

<x-layouts.cinebook title="Your tickets · CineBook">
    <div class="flex flex-col gap-7 bg-[#faf9f6] px-4 py-8 text-[#55564f] sm:px-8 lg:px-16 lg:py-10">

        {{-- Page heading --}}
        <div>
            <p class="mb-4 text-sm uppercase leading-[1.6] text-[#77766f]">
                My bookings / {{ $ref }} / Tickets
            </p>

            <h1 class="text-[28px] font-bold leading-[1.2] text-[#252820] sm:text-[38px]">
                Your tickets to another world
            </h1>
        </div>

        {{-- Booking summary and actions --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-center print:hidden">

            <p class="text-xs font-semibold text-[#27866b] md:flex-1">
                ✓ Confirmed · {{ $n }} valid {{ $n === 1 ? 'ticket' : 'tickets' }}
            </p>

            <p class="text-sm leading-[1.45] text-[#55564f] md:flex-1">
                {{ $guest }} · {{ $n }} {{ $n === 1 ? 'seat' : 'seats' }}
            </p>

            <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    onclick="window.print()"
                    class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm font-medium text-white transition hover:brightness-110"
                >
                    Print / Save as PDF
                </button>

                <!-- <a
                    href="{{ $b ? route('booking.recovery-states', $b) : '#' }}"
                    class="rounded-lg border border-[#deded5] bg-white px-5 py-[13px] text-sm font-medium text-[#252820] transition hover:bg-[#f1f0e9]"
                >
                    Booking details
                </a> -->
            </div>
        </div>

        {{-- QR information --}}
        <div class="rounded-lg border border-[#e5e2d8] bg-[#f3f1e9] p-4 text-sm leading-[1.6] text-[#45463f] print:hidden">
            <p class="mb-1 font-semibold">ⓘ One guest, one QR</p>

            <p>
                Present each ticket separately. Each code contains only an opaque ticket
                identifier, never your personal details. Save both tickets before you arrive.
            </p>
        </div>

        {{-- Individual tickets --}}
        <div class="grid gap-8 md:grid-cols-2">
            @forelse ($list as $t)
                <article class="flex flex-col gap-6 break-inside-avoid rounded-2xl border border-[#e5e2d8] bg-white p-7 text-[#55564f] shadow-sm">

                    {{-- Ticket movie and showtime details --}}
                    <div class="text-sm leading-[1.6]">
                        <p class="font-semibold uppercase tracking-wide text-[#77766f]">
                            CineBook &nbsp;/&nbsp; ADMIT ONE
                        </p>

                        <p class="mt-4 text-[26px] font-bold text-[#252820]">
                            {{ $movie }}
                        </p>

                        <p class="mt-1">
                            {{ $cinema }}
                        </p>

                        <p>
                            {{ $start->format('l, F j, Y · H:i') }}
                        </p>
                    </div>

                    {{-- Seat and QR code --}}
                    <div class="flex items-center gap-6">
                        <div class="min-w-0 flex-1 text-xs leading-[1.8]">
                            <p class="text-5xl font-bold text-[#252820]">
                                {{ $t['seat'] }}
                            </p>

                            <p class="mt-2">
                                {{ $t['type'] ?? 'Standard' }} seat
                            </p>

                            <p class="mt-1 break-words">
                                {{ $ref }}
                            </p>

                            <p class="mt-1 break-words">
                                Ticket {{ $t['code'] }}
                            </p>
                        </div>

                        <div class="size-[148px] shrink-0 overflow-hidden rounded-lg border border-[#e5e2d8] bg-white p-2 [&>svg]:size-full">
                            @if (!empty($t['qr']))
                                {!! $t['qr'] !!}
                            @else
                                <div class="flex h-full items-center justify-center">
                                    <p class="text-center text-xs text-red-600">
                                        QR unavailable
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Entry information --}}
                    <p class="border-t border-[#eeece5] pt-4 text-xs leading-[1.6] text-[#66675f]">
                        Entry opens {{ $start->copy()->subHour()->format('H:i') }}
                        · Closes {{ $start->copy()->addMinutes(30)->format('H:i') }}.
                        Present this individual QR at the entrance. Each ticket admits one guest once.
                    </p>
                </article>
            @empty
                <div class="rounded-xl border border-[#e5e2d8] bg-white p-8 text-center md:col-span-2">
                    <h2 class="text-lg font-semibold text-[#252820]">
                        No tickets available
                    </h2>

                    <p class="mt-2 text-sm text-[#77766f]">
                        There are no tickets to display for this booking.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Footer information --}}
        <div class="text-sm leading-[1.6] text-[#55564f] print:hidden">
            <p>
                Please arrive from {{ $start->copy()->subHour()->format('H:i') }}.
                Scanning closes at {{ $start->copy()->addMinutes(30)->format('H:i') }}.
                Used or cancelled tickets won’t admit entry.
            </p>

            <a
                href="{{ route('bookings.index') }}"
                class="mt-4 inline-block font-medium text-[#347bff] transition hover:text-[#245fd1]"
            >
                ← Back to My bookings
            </a>
        </div>
    </div>
</x-layouts.cinebook>