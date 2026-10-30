@extends('layouts.admin')

@section('content')
    @php
        $tones = [
            'info' => 'text-[#87beff]',
            'warning' => 'text-[#f0c47a]',
            'success' => 'text-green-400',
            'muted' => 'text-[#a0aec5]',
            'danger' => 'text-red-400',
        ];
        $card = 'rounded-2xl border border-[#283750] bg-[#101a2d] p-6';
        $btnGhost = 'rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:bg-[#1e2f4d]';
        $btnOff = 'cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b]/60 px-5 py-[13px] text-sm text-white/40';
        $canResend = Route::has('admin.bookings.resend');
        $canCancel = Route::has('admin.bookings.cancel');
        $canCustomer = $booking->user && Route::has('admin.customers.show');
        $canPayment = Route::has('admin.payments.show');
    @endphp

    {{-- Heading --}}
    <div>
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Reservations / Bookings</p>
        <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Booking {{ $booking->booking_reference }}</h1>
    </div>

    {{-- Status row --}}
    <div class="flex flex-wrap items-center gap-4">
        <p class="flex-1 text-xs {{ $tones[$state['tone']] ?? 'text-[#a0aec5]' }}">
            {{ $status === 'confirmed' ? '✓ ' : '' }}{{ $state['label'] }}
        </p>
        <p class="flex-1 text-[13px] leading-[1.45] text-[#a0aec5]">{{ $summary }}</p>
        @if ($status === 'confirmed')
            {{-- TODO: enable once the confirmation e-mail flow adds admin.bookings.resend --}}
            @if ($canResend)
                <form method="POST" action="{{ route('admin.bookings.resend', $booking) }}">
                    @csrf
                    <button type="submit" class="{{ $btnGhost }}">Resend confirmation</button>
                </form>
            @else
                <span class="{{ $btnOff }}">Resend confirmation</span>
            @endif
        @endif
    </div>

    <div class="flex flex-col gap-6 xl:flex-row xl:items-start">

        {{-- LEFT --}}
        <div class="flex min-w-0 flex-1 flex-col gap-4">

            {{-- Reservation summary --}}
            <div class="{{ $card }} flex gap-5">
                <div class="h-[180px] w-[104px] shrink-0">
                    @if ($movie?->poster_url)
                        <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }} poster"
                            class="size-full rounded-2xl object-cover">
                    @else
                        <div class="flex size-full items-center justify-center rounded-2xl border border-dashed border-[#283750] text-xs text-[#a0aec5]">
                            No poster
                        </div>
                    @endif
                </div>

                <div class="min-w-0 flex-1 text-sm leading-[1.8] text-[#a0aec5]">
                    <p class="text-2xl font-bold text-[#f4f7ff]">{{ $movie?->title }}</p>
                    <p>{{ $showtime->starts_at->format('D, F j, Y · H:i') }}</p>
                    <p>CineBook Downtown · {{ $showtime->screen?->name }}</p>
                    <p>{{ $movie?->duration_minutes }} min{{ $movie?->rating ? ' · ' . $movie->rating : '' }}</p>
                    <p>{{ $seatSummary ?: 'No seats' }}{{ $seatSummary ? ' · ' . $typeLabel : '' }}</p>
                    <p>{{ $booking->booking_reference }}</p>
                    <p>Total · {{ $total }} {{ $booking->currency }}</p>
                </div>
            </div>

            {{-- Seats & admission --}}
            <div class="{{ $card }} flex flex-col gap-5">
                <h2 class="text-[22px] font-bold leading-[1.13] text-[#f4f7ff]">Seats &amp; admission</h2>

                <div class="overflow-x-auto rounded-xl border border-[#283750] bg-[#0c1424] p-4">
                    <table class="w-full min-w-[520px] text-left text-xs">
                        <thead>
                            <tr class="uppercase text-[#a0aec5]">
                                <th class="h-[44px] font-normal text-[#f4f7ff]">Seat / Type</th>
                                <th class="h-[44px] font-normal">Ticket</th>
                                <th class="h-[44px] font-normal">Scan state</th>
                                <th class="h-[44px] font-normal">Scanned at</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($seatRows as $row)
                                <tr class="border-t border-[#283750]">
                                    <td class="h-[48px] text-[#f4f7ff]">{{ $row['seat'] }}</td>
                                    <td class="h-[48px] text-[#a0aec5]">{{ $row['ticket'] }}</td>
                                    <td class="h-[48px] {{ $row['scan'] === 'Void' ? 'text-red-400' : 'text-[#a0aec5]' }}">{{ $row['scan'] }}</td>
                                    <td class="h-[48px] text-[#a0aec5]">{{ $row['scanned_at'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-[#a0aec5]">No seats on this booking.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Payment & refund history --}}
            <div class="{{ $card }} flex flex-col gap-5">
                <h2 class="text-[22px] font-bold leading-[1.13] text-[#f4f7ff]">Payment &amp; refund history</h2>

                <div class="overflow-x-auto rounded-xl border border-[#283750] bg-[#0c1424] p-4">
                    <table class="w-full min-w-[520px] text-left text-xs">
                        <thead>
                            <tr class="uppercase text-[#a0aec5]">
                                <th class="h-[44px] font-normal text-[#f4f7ff]">Record</th>
                                <th class="h-[44px] font-normal">Amount</th>
                                <th class="h-[44px] font-normal text-[#87beff]">State</th>
                                <th class="h-[44px] font-normal">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paymentRows as $row)
                                <tr class="border-t border-[#283750]">
                                    <td class="h-[48px] text-[#f4f7ff]">{{ $row['record'] }}</td>
                                    <td class="h-[48px] text-[#a0aec5]">{{ $row['amount'] }}</td>
                                    <td class="h-[48px] {{ $row['state'] === 'Failed' ? 'text-red-400' : 'text-[#87beff]' }}">{{ $row['state'] }}</td>
                                    <td class="h-[48px] text-[#a0aec5]">{{ $row['time'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($canPayment && $booking->payments->isNotEmpty())
                    <a href="{{ route('admin.payments.show', $booking->payments->sortByDesc('created_at')->first()) }}"
                        class="text-[13px] leading-[1.45] text-[#87beff] hover:underline">Open read-only payment details →</a>
                @else
                    <p class="text-[13px] leading-[1.45] text-[#a0aec5]/60">Open read-only payment details →</p>
                @endif
            </div>
        </div>

        {{-- RIGHT --}}
        <div class="flex w-full shrink-0 flex-col gap-4 xl:w-[330px]">

            {{-- Customer --}}
            <div class="{{ $card }} flex flex-col gap-5">
                <div class="text-sm leading-[1.6] text-[#a0aec5]">
                    <h2 class="mb-4 text-[22px] font-bold leading-[1.2] text-[#f4f7ff]">Customer</h2>
                    <p>{{ $booking->user?->name ?? 'Deleted user' }}</p>
                    <p class="mt-4 break-all">{{ $booking->user?->email ?? '—' }}</p>
                    <p class="mt-4">
                        @if ($booking->user?->email_verified_at)
                            ✓ Email verified
                        @else
                            ! Email not verified
                        @endif
                    </p>
                </div>
                @if ($canCustomer)
                    <a href="{{ route('admin.customers.show', $booking->user) }}" class="self-start {{ $btnGhost }}">View customer</a>
                @endif
            </div>

            {{-- Event timeline --}}
            <div class="{{ $card }}">
                <h2 class="mb-4 text-[22px] font-bold leading-[1.2] text-[#f4f7ff]">Event timeline</h2>
                <ul class="flex flex-col text-sm leading-[1.6] text-[#a0aec5]">
                    @foreach ($timeline as $event)
                        <li class="{{ $loop->first ? 'text-[#87beff]' : '' }}">• {{ $event['time'] }} · {{ $event['text'] }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Email notice (mail delivery isn't tracked yet, see controller) --}}
            @if ($emailPending)
                <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
                    <p>ⓘ Email delayed · booking confirmed</p>
                    <p>Retrying delivery won’t change seats, ticket validity or payment status.</p>
                </div>
            @endif

            {{-- Admin actions --}}
            <div class="{{ $card }} flex flex-col gap-5">
                <div class="text-sm leading-[1.6] text-[#a0aec5]">
                    <h2 class="mb-4 text-[22px] font-bold leading-[1.2] text-[#f4f7ff]">Admin actions</h2>
                    @if ($status === 'confirmed')
                        <p>Cancellation/refund requires a reason and impact confirmation.</p>
                    @else
                        <p>No actions available for a {{ strtolower($state['label']) }} booking.</p>
                    @endif
                </div>

                @if ($status === 'confirmed')
                    {{-- TODO: enable once the admin cancel + refund flow adds admin.bookings.cancel --}}
                    @if ($canCancel)
                        <a href="{{ route('admin.bookings.cancel', $booking) }}"
                            class="self-start rounded-lg border border-red-400 bg-[#3d222e] px-5 py-[13px] text-sm text-red-300 hover:bg-[#4a2733]">Cancel &amp; refund…</a>
                    @else
                        <span class="cursor-not-allowed self-start rounded-lg border border-red-400/40 bg-[#3d222e]/60 px-5 py-[13px] text-sm text-red-300/60">Cancel &amp; refund…</span>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div>
        <a href="{{ route('admin.bookings.index') }}" class="text-sm text-[#a0aec5] hover:text-white">← Back to bookings</a>
    </div>
@endsection