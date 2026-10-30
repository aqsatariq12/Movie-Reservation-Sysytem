@extends('layouts.admin')

@section('content')
    @php
        $tones = [
            'ok'    => 'text-[#347bff]',
            'warn'  => 'text-amber-400',
            'muted' => 'text-[#a0aec5]',
        ];
        $reference = 'PAY-' . str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT);
        $when = $payment->paid_at ?? $payment->created_at;
    @endphp

    {{-- Heading --}}
    <div class="flex items-center">
        <div class="flex-1">
            <p class="text-xs font-semibold uppercase text-[#347bff]">Cinebook Downtown / Reservations / Payments</p>
            <h1 class="text-[32px] font-bold leading-normal text-[#f4f7ff]">Payment {{ $reference }}</h1>
        </div>
    </div>

    {{-- Status row --}}
    <div class="flex items-center gap-4">
        <p class="flex-1 text-xs {{ $state['tone'] === 'brand' ? 'text-[#347bff]' : 'text-[#a0aec5]' }}">
            {{ $payment->status->value === 'paid' ? '✓ ' : '' }}{{ $state['label'] }}
        </p>
        <p class="flex-1 text-xs uppercase text-[#a0aec5]">• Stripe test mode</p>
        <p class="flex-1 text-[13px] leading-[1.45] text-[#a0aec5]">Read-only record · No card details stored</p>
    </div>

    <div class="flex items-start gap-6">

        {{-- Left column --}}
        <div class="flex min-w-0 flex-1 flex-col gap-4">

            {{-- Payment summary --}}
            <section class="flex flex-col gap-5 rounded-3xl border border-[#283750] bg-[#0c1424] p-6">
                <p class="text-[44px] font-bold leading-[1.2] text-[#f4f7ff]">{{ $amount }}</p>

                <dl class="flex flex-col gap-3.5 text-sm leading-[1.6] text-[#a0aec5]">
                    <div>Currency · {{ strtoupper($payment->currency) }}</div>
                    <div>
                        Booking · {{ $booking?->booking_reference ?? '—' }}
                        @if ($bookingState) · {{ $bookingState['label'] }} @endif
                    </div>
                    <div>Customer · {{ $booking?->user?->name ?? '—' }}</div>
                    <div>Provider · {{ ucfirst($payment->provider) }} hosted Checkout · Test</div>
                    <div class="break-all">Provider payment ID · {{ $payment->provider_payment_id ?? '—' }}</div>
                    <div>
                        Created / verified ·
                        {{ $payment->created_at?->format('M j · H:i') }} /
                        {{ $payment->paid_at?->format('H:i') ?? '—' }}
                    </div>
                    <div>Refunded amount · {{ $refundedAmount }}</div>
                </dl>

                @if ($booking)
                    <a href="{{ Route::has('admin.bookings.show') ? route('admin.bookings.show', $booking) : '#' }}"
                       class="w-fit rounded-xl bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#2f6fe6]">
                        Open related booking →
                    </a>
                @endif
            </section>

            {{-- Verified provider events --}}
            <section class="flex flex-col gap-5 rounded-3xl border border-[#283750] bg-[#0c1424] p-6">
                <h2 class="text-[22px] font-bold leading-[1.13] text-[#f4f7ff]">Verified provider events</h2>

                <div class="overflow-x-auto rounded-2xl border border-[#283750] p-4">
                    <table class="w-full min-w-[560px] table-fixed text-xs">
                        <thead>
                            <tr class="text-left uppercase text-[#f4f7ff]">
                                <th class="h-[58px] pr-3 font-normal">Received</th>
                                <th class="h-[58px] pr-3 font-normal text-[#a0aec5]">Event</th>
                                <th class="h-[58px] pr-3 font-normal text-[#a0aec5]">Verification</th>
                                <th class="h-[58px] font-normal text-[#a0aec5]">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($events as $event)
                                <tr>
                                    <td class="h-[58px] pr-3 text-[#f4f7ff]">{{ $event['time'] }}</td>
                                    <td class="h-[58px] truncate pr-3 text-[#a0aec5]" title="{{ $event['type'] }}">{{ $event['type'] }}</td>
                                    <td class="h-[58px] pr-3 text-[#a0aec5]">Signature verified</td>
                                    <td class="h-[58px] text-[#a0aec5]">{{ $event['result'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-[#a0aec5]">No provider events recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- Right column --}}
        <div class="flex w-[350px] shrink-0 flex-col gap-4">

            {{-- Reconciliation --}}
            <section class="rounded-3xl border border-[#283750] bg-[#0c1424] p-6 text-sm leading-[1.6] text-[#a0aec5]">
                <h2 class="text-[22px] font-bold leading-[1.2] text-[#f4f7ff]">Reconciliation</h2>

                <p class="mt-4 {{ $tones[$reconciliation['tone']] }}">
                    {{ $reconciliation['icon'] }} {{ $reconciliation['headline'] }}
                </p>

                <p class="mt-4">{{ $reconciliation['message'] }}</p>

                <ul class="mt-4">
                    @foreach ($timeline as $item)
                        <li class="{{ $loop->first ? 'text-[#347bff]' : '' }}">
                            •&nbsp; {{ $item['time'] }} · {{ $item['text'] }}
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Notice --}}
            <div class="rounded-2xl border border-[#347bff]/30 bg-[#347bff]/10 p-4 text-sm leading-[1.6] text-[#f4f7ff]">
                <p>ⓘ Webhook processing</p>
                <p>Duplicate events are ignored safely. Failed webhook events appear on Dashboard and in Failed jobs for investigation.</p>
            </div>
        </div>
    </div>
@endsection