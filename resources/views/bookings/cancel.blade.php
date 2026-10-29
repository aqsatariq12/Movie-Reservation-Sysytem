{{-- resources/views/bookings/cancel.blade.php --}}
<x-layouts.cinebook title="Cancel booking">
    <div class="bg-[#f7f4ee]">
        <div class="mx-auto flex max-w-[1312px] flex-col gap-7 px-6 py-10 sm:px-16">

            <div>
                <p class="text-sm uppercase leading-[1.6] text-[#6b7280]">
                    <a href="{{ route('bookings.index') }}" class="hover:text-[#347bff]">My bookings</a>
                    / <a href="{{ route('bookings.show', $booking) }}" class="hover:text-[#347bff]">{{ $booking->booking_reference }}</a>
                    / Cancel
                </p>
                <h1 class="mt-3 text-[38px] font-bold leading-[1.2] text-[#1f2937]">Cancel your movie night?</h1>
            </div>

            <div class="flex flex-col gap-10 lg:flex-row lg:items-center">

                <div class="flex min-w-0 flex-1 flex-col gap-4">
                    <section class="flex flex-col gap-5 rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                        <div class="text-sm leading-[1.6] text-[#6b7280]">
                            <p class="uppercase">◷ Please review before cancelling</p>
                            <h2 class="mt-4 text-[30px] font-bold leading-[1.2] text-[#1f2937]">{{ $seatHeadline }}</h2>
                            <p class="mt-4">
                                Your entire reservation for {{ $seat['sentence'] }} will be cancelled.
                                {{ $ticketsPhrase }} void immediately and the {{ \Illuminate\Support\Str::plural('seat', $seat['count']) }} released.
                            </p>
                            <p class="mt-4">Refund requested after cancellation &nbsp;·&nbsp; {{ $total }} {{ $booking->currency }} · Full amount</p>
                            <p class="mt-4">Eligible until &nbsp;·&nbsp; {{ $deadline }}</p>
                            <p class="mt-4">Ticket scan status &nbsp;·&nbsp; {{ $scanText }}</p>
                        </div>

                        <div class="w-full rounded-xl border border-[#f3d1b7] bg-[#fff7ed] p-4 text-sm leading-[1.6] text-[#1f2937]">
                            <p>! Cancellation can’t be undone</p>
                            <p>Your QR tickets will stop working. Refund processing is separate from the cancellation and may take time to complete.</p>
                        </div>

                        @if ($errors->any())
    <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">
        <p class="font-semibold">Cancellation could not be completed.</p>

        <ul class="mt-2 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

                        <div class="flex flex-wrap items-center gap-4">
                            <a href="{{ route('bookings.show', $booking) }}"
                                class="rounded-xl border border-[#e3d8c8] bg-white px-5 py-[13px] text-sm font-semibold text-[#1f2937] hover:bg-[#f7f4ee]">
                                Keep my booking
                            </a>

                            @if (\Illuminate\Support\Facades\Route::has('bookings.cancel'))
                                <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                                    @csrf
                                    <button type="submit"
                                        class="rounded-xl border border-[#f2808c] bg-[#b42335] px-5 py-[13px] text-sm font-semibold text-white hover:bg-[#9a1d2d]">
                                        Yes, cancel &amp; request {{ $totalShort }} refund
                                    </button>
                                </form>
                            @else
                                {{-- UI phase: the POST route doesn't exist yet. --}}
                                <button type="button" disabled
                                    class="cursor-not-allowed rounded-xl border border-[#f2808c] bg-[#b42335] px-5 py-[13px] text-sm font-semibold text-white opacity-70">
                                    Yes, cancel &amp; request {{ $totalShort }} refund
                                </button>
                            @endif
                        </div>
                    </section>

                    <p class="text-[13px] leading-[1.45] text-[#6b7280]">
                        You’re within the cancellation window. This is a full-booking cancellation, not a seat-by-seat change.
                    </p>
                </div>

                <x-bookings.summary-card class="w-full shrink-0 lg:w-[440px]"
                    :booking="$booking" :seat-summary="$seat['summary']" :type-label="$seat['typeLabel']" :total="$total" />
            </div>
        </div>
    </div>
</x-layouts.cinebook>