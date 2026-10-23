@extends('layouts.admin')

@section('content')
    @php
        $n = $impact['paid_bookings'];
        $money = fn (int $c) => \App\Models\Showtime::money($c);
    @endphp

    <div>
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Programming / Showtimes</p>
        <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Cancel showtime</h1>
    </div>

    <div class="rounded-lg border border-red-400/40 bg-[#3d222e] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
        <p>! Booked show · cancellation, not deletion</p>
        <p>Cancellation voids all tickets, releases seats and cancels every booking for this show. This action can't be undone. Refunds are handled separately.</p>
    </div>

    @error('showtime_error')
        <div class="rounded-lg bg-[#3d222e] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
            <p>! Showtime not cancelled</p>
            <p>{{ $message }}</p>
        </div>
    @enderror

    <form method="POST" action="{{ route('admin.showtimes.cancel.store', $showtime) }}"
          class="flex flex-col gap-6 xl:flex-row xl:items-start">
        @csrf

        {{-- Main panel --}}
        <div class="flex min-w-0 flex-1 flex-col gap-5 rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
            <div>
                <h2 class="text-[26px] font-bold leading-[1.2] text-[#f4f7ff]">
                    {{ $showtime->movie?->title }} · {{ $showtime->screen->name }}
                </h2>
                <p class="mt-2 text-sm text-[#a0aec5]">
                    {{ $showtime->starts_at->format('l, M j, Y · H:i') }} → {{ $showtime->ends_at->format('H:i') }}
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-[#283750] bg-[#0c1424] p-6">
                    <p class="text-[32px] font-bold leading-none text-[#f4f7ff]">{{ $n }}</p>
                    <p class="mt-4 text-sm text-[#a0aec5]">Paid bookings affected</p>
                </div>
                <div class="rounded-xl border border-[#283750] bg-[#0c1424] p-6">
                    <p class="text-[32px] font-bold leading-none text-[#f4f7ff]">{{ $impact['tickets'] }}</p>
                    <p class="mt-4 text-sm text-[#a0aec5]">Tickets to void</p>
                </div>
                <div class="rounded-xl border border-[#283750] bg-[#0c1424] p-6">
                    <p class="text-[32px] font-bold leading-none text-[#f4f7ff]">{{ $money($impact['refund_cents']) }}</p>
                    <p class="mt-4 text-sm text-[#a0aec5]">Full refund total · USD</p>
                </div>
            </div>

            <p class="text-sm text-[#a0aec5]">
                Active holds to release &nbsp;·&nbsp;
                {{ $impact['holds'] }} {{ \Illuminate\Support\Str::plural('hold', $impact['holds']) }}
                · {{ $impact['held_seats'] }} {{ \Illuminate\Support\Str::plural('seat', $impact['held_seats']) }}
            </p>

            <label for="reason" class="flex flex-col rounded-lg border {{ $errors->has('reason') ? 'border-red-400' : 'border-[#283750]' }} bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                <span class="text-[13px] uppercase leading-[1.7] {{ $errors->has('reason') ? 'text-red-400' : 'text-[#a0aec5]' }}">Cancellation reason *</span>
                <textarea id="reason" name="reason" rows="4" required maxlength="500"
                          placeholder="e.g. Projector failure. {{ $showtime->screen->name }} is unavailable for this show."
                          class="w-full resize-none bg-transparent text-[13px] text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]/60">{{ old('reason') }}</textarea>
            </label>
            @error('reason') <p class="-mt-3 text-[13px] text-red-400">{{ $message }}</p> @enderror

            <label class="flex items-start gap-2 text-sm text-[#f4f7ff]">
                <input id="confirm" type="checkbox" name="confirm" value="1" class="mt-1 accent-[#347bff]" @checked(old('confirm'))>
                <span>I understand all {{ $n }} paid {{ \Illuminate\Support\Str::plural('booking', $n) }} will be cancelled and their tickets voided.</span>
            </label>
            @error('confirm') <p class="-mt-3 text-[13px] text-red-400">{{ $message }}</p> @enderror

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('admin.showtimes.edit', $showtime) }}"
                   class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:bg-[#1e2f4d]">Keep showtime</a>
                <button id="cancel-btn" type="submit"
                        class="rounded-lg border border-red-400 bg-[#3d222e] px-5 py-[13px] text-sm text-red-300 hover:bg-[#4a2733] disabled:cursor-not-allowed disabled:opacity-50">
                    Cancel show
                </button>
            </div>
        </div>

        {{-- Side column --}}
        <div class="flex w-full shrink-0 flex-col gap-4 xl:w-[310px]">
            <div class="flex flex-col gap-4 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6 text-sm leading-[1.6] text-[#a0aec5]">
                <p class="text-xs uppercase">◷ Impact summary</p>
                <p>Affected customers will be notified by email once the notification flow is added. An email failure never changes the cancellation.</p>
                <p>Pending payments &nbsp;·&nbsp; Released and cancelled</p>
                <p>Refund requests &nbsp;·&nbsp; Handled by the payments flow</p>
                <p>Action, reason and actor are recorded in the audit log.</p>
            </div>

            <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
                <p>ⓘ No duplicate refunds</p>
                <p>Existing refund records are reconciled; the same booking will not receive a second refund request.</p>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const box = document.getElementById('confirm');
            const btn = document.getElementById('cancel-btn');
            const sync = () => { btn.disabled = !box.checked; };
            box.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection 