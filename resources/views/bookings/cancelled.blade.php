{{-- resources/views/bookings/cancelled.blade.php --}}
@php
    $headline = match ($refundState) {
        'succeeded' => 'Booking cancelled. Refund completed.',
        'failed'    => 'Booking cancelled. Refund needs review.',
        'none'      => 'Booking cancelled.',
        default     => 'Booking cancelled. Refund requested.',
    };
    $badge = match ($refundState) {
        'succeeded' => '✓ Refunded · Succeeded',
        'failed'    => '! Cancelled · Refund failed',
        'none'      => 'Cancelled',
        default     => '◷ Cancelled · Refund pending',
    };
    $showtime = $booking->showtime;
    $noun = \Illuminate\Support\Str::plural('seat', $seat['count']);
@endphp

<x-layouts.cinebook title="Booking cancelled">
    <div class="bg-[#f7f4ee]">
        <div class="mx-auto flex max-w-[1312px] flex-col gap-7 px-6 py-10 sm:px-16">

            <div>
                <p class="text-sm uppercase leading-[1.6] text-[#6b7280]">
                    <a href="{{ route('bookings.index') }}" class="hover:text-[#347bff]">My bookings</a>
                    / {{ $booking->booking_reference }}
                </p>
                <h1 class="mt-3 text-[38px] font-bold leading-[1.2] text-[#1f2937]">{{ $headline }}</h1>
            </div>

            <div class="flex flex-col gap-8 lg:flex-row lg:items-center">

                {{-- LEFT --}}
                <div class="flex min-w-0 flex-1 flex-col gap-4">
                    <section class="flex flex-col gap-5 rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                        <div class="text-sm leading-[1.6] text-[#6b7280]">
                            <p class="uppercase">{{ $badge }}</p>
                            <h2 class="mt-4 text-[30px] font-bold leading-[1.2] text-[#1f2937]">
                                Your {{ $noun }} {{ $seat['count'] === 1 ? 'has' : 'have' }} been released.
                            </h2>
                            <p class="mt-4">
                                {{ $booking->showtime->movie->title }} · {{ $showtime->starts_at->format('M j · H:i') }}
                                · {{ $showtime->screen->name }} · {{ $seat['sentence'] }}
                                {{ $seat['count'] === 1 ? 'is' : 'are' }} no longer reserved.
                                {{ $seat['count'] === 1 ? 'Your ticket is' : 'Tickets are' }} void.
                            </p>
                            @if ($refundState !== 'none')
                                <p class="mt-4">Full refund requested &nbsp;·&nbsp; {{ $total }} {{ $booking->currency }}</p>
                                <p class="mt-4">Requested at &nbsp;·&nbsp; {{ $requestedAt }}</p>
                                <p class="mt-4">Refund reference &nbsp;·&nbsp; {{ $refundRef }}</p>
                            @endif
                        </div>

                        @if ($refundState === 'pending')
                            <div class="w-full rounded-2xl border border-[#e3d8c8] bg-[#fff4d6] p-4 text-sm leading-[1.6] text-[#1f2937]">
                                <p>ⓘ Requested is not completed</p>
                                <p>The refund is pending with Stripe in test mode. We’ll update this page when it succeeds. Your booking remains cancelled.</p>
                            </div>
                        @elseif ($refundState === 'succeeded')
                            <div class="w-full rounded-2xl border border-[#e3d8c8] bg-[#f4f7ff] p-4 text-sm leading-[1.6] text-[#1f2937]">
                                <p>✓ Refund succeeded</p>
                                <p>
                                    Stripe verified the full {{ $total }} refund{{ $completedAt ? ' at ' . $completedAt : '' }}.
                                    Tickets remain void and the seats remain released.
                                </p>
                            </div>
                        @elseif ($refundState === 'failed')
                            <div class="w-full rounded-2xl border border-[#e3d8c8] bg-[#fff0f0] p-4 text-sm leading-[1.6] text-[#1f2937]">
                                <p>! Refund failed — cinema team reviewing</p>
                                <p>Your booking stays cancelled and the team can retry the refund.</p>
                            </div>
                        @endif

                        <a href="{{ route('bookings.index') }}"
                            class="rounded-xl bg-[#347bff] px-5 py-[13px] text-sm font-semibold text-white hover:bg-[#2a68dc]">
                            Back to my bookings
                        </a>
                    </section>

                    @if ($refundState === 'succeeded')
                        <section class="rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                            <div class="text-sm leading-[1.6] text-[#6b7280]">
                                <h2 class="text-[22px] font-bold leading-[1.2] text-[#1f2937]">Refund completed</h2>
                                <p class="mt-4 uppercase">✓ Refunded · Succeeded</p>
                                <p class="mt-4">Refund total &nbsp;·&nbsp; {{ $total }} {{ $booking->currency }} · Completed</p>
                            </div>
                        </section>
                    @endif
                </div>

                {{-- RIGHT --}}
                <div class="flex w-full shrink-0 flex-col gap-4 lg:w-[420px]">
                    <section class="rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                        <h2 class="text-[22px] font-bold leading-[1.2] text-[#1f2937]">Cancellation timeline</h2>
                        <ul class="mt-4 text-sm leading-[1.6]">
                            @foreach ($timeline as $event)
                                <li class="{{ $loop->first ? 'text-[#347bff]' : 'text-[#6b7280]' }}">
                                    •&nbsp; {{ $event['time'] }} · {{ $event['text'] }}
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    <div class="rounded-2xl border border-[#e3d8c8] bg-[#fff0f0] p-4 text-sm leading-[1.6] text-[#1f2937]">
                        <p>! If a refund fails</p>
                        <p>Your booking stays cancelled. We’ll show “Refund failed — cinema team reviewing” and the team can retry. You won’t receive valid tickets again.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.cinebook>