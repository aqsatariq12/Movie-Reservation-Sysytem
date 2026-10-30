@php
    $b      = $booking ?? null;
   $start  = \Illuminate\Support\Carbon::parse(data_get($b, 'showtime.starts_at'));
$movie  = data_get($b, 'showtime.movie.title');
$cinema = data_get($b, 'showtime.screen.name');
$ref    = data_get($b, 'booking_reference');
$guest  = auth()->user()->name ?? 'Guest';
$list   = $tickets ?? [];
$n      = count($list);
$usedCount = collect($list)->where('status', 'used')->count();
$validCount = $n - $usedCount;
   
@endphp

<x-layouts::site title="Your tickets · CineBook">
    <div class="flex flex-col gap-7 px-4 py-8 sm:px-8 lg:px-16 lg:py-10">

        <div>
            <p class="mb-4 text-sm uppercase leading-[1.6]">My bookings / {{ $ref }} / Tickets</p>
            <h1 class="text-[28px] font-bold leading-[1.2] text-[#f4f7ff] sm:text-[38px]">Your tickets to another world</h1>
        </div>

        <div class="flex flex-col gap-4 md:flex-row md:items-center print:hidden">
            <p class="text-xs text-[#60d4ae] md:flex-1">
    ✓ {{ $validCount }} valid · {{ $usedCount }} admitted · {{ $n }} total tickets
</p>
            <p class="text-sm leading-[1.45] md:flex-1">{{ $guest }} · {{ $n }} {{ $n === 1 ? 'seat' : 'seats' }}</p>
            <div class="flex flex-wrap gap-3">
                <button type="button" onclick="window.print()" class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:brightness-110">Print / Save as PDF</button>
                <a href="{{ $b ? route('booking.recovery-states', $b) : '#' }}" class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:brightness-125">Booking details</a>
            </div>
        </div>

        <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff] print:hidden">
            <p>ⓘ One guest, one QR</p>
            <p>Present each ticket separately. Each code contains only an opaque ticket identifier, never your personal details. Save both tickets before you arrive.</p>
        </div>

        <div class="grid gap-8 md:grid-cols-2">
            @foreach ($list as $t)
                <article class="flex flex-col gap-6 rounded-2xl border border-[#283750] bg-[#101a2d] p-7 break-inside-avoid">
                    <div class="text-sm leading-[1.6]">
                        <p>cinebook &nbsp;/&nbsp; ADMIT ONE</p>
                        <p class="mt-4 text-[26px] font-bold text-[#f4f7ff]">{{ $movie }}</p>
                        <p>{{ $cinema }}</p>
                        <p>{{ $start->format('l, F j, Y · H:i') }}</p>
                    </div>

                    <div class="flex items-center gap-6">
                        <div class="min-w-0 flex-1 text-xs leading-[1.8]">
                            <p class="text-5xl font-bold text-[#f4f7ff]">{{ $t['seat'] }}</p>
                            <p>{{ $t['type'] ?? 'Standard' }} seat</p>
                            @if (strtolower($t['status'] ?? '') === 'used')
    <p class="mt-2 inline-block rounded-md bg-red-100 px-3 py-1 font-bold text-red-700">
        ✓ SCANNED — ALREADY ADMITTED
    </p>
@elseif (strtolower($t['status'] ?? '') === 'valid')
    <p class="mt-2 inline-block rounded-md bg-green-100 px-3 py-1 font-bold text-green-700">
        VALID — NOT SCANNED
    </p>
@else
    <p class="mt-2 inline-block rounded-md bg-gray-200 px-3 py-1 font-bold text-gray-700">
        {{ strtoupper($t['status'] ?? 'UNKNOWN') }}
    </p>
@endif
                            <p>{{ $ref }}</p>
                            <p>Ticket {{ $t['code'] }}</p>
                        </div>
                        <div class="size-[148px] shrink-0 overflow-hidden rounded-lg bg-white p-2 [&>svg]:size-full">
                            @if (!empty($t['qr']))
                                {!! $t['qr'] !!}
                          @else
    <p class="text-center text-xs text-red-600">
        QR unavailable
    </p>
@endif
                        </div>
                    </div>

                    <p class="text-xs leading-[1.6]">Entry opens {{ $start->copy()->subHour()->format('H:i') }} · Closes {{ $start->copy()->addMinutes(30)->format('H:i') }}. Present this individual QR at the entrance. Each ticket admits one guest once.</p>
                </article>
            @endforeach
        </div>

        <div class="text-sm leading-[1.6] print:hidden">
            <p>Please arrive from {{ $start->copy()->subHour()->format('H:i') }}. Scanning closes at {{ $start->copy()->addMinutes(30)->format('H:i') }}. Used or cancelled tickets won’t admit entry.</p>
            <a href="{{ route('bookings.index') }}" class="mt-4 inline-block hover:text-white">← Back to My bookings</a>
        </div>
    </div>
    <style>
@media print {
    body,
    body * {
        color: #000000 !important;
    }

    article {
        background: #ffffff !important;
        border-color: #cccccc !important;
    }

    article .bg-white {
        background: #ffffff !important;
    }

    article svg {
        color: #000000 !important;
    }
     article {
        overflow: visible !important;
        break-inside: avoid !important;
    }

    article svg {
        display: block !important;
        width: 200px !important;
        height: 200px !important;
        max-width: 100% !important;
        overflow: visible !important;
        opacity: 1 !important;
        filter: none !important;
        background: #ffffff !important;
    }
}
</style>
</x-layouts::site>