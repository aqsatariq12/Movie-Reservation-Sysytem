@extends('layouts.admin')

@section('content')
    @php
        $card = 'rounded-2xl border border-[#283750] bg-[#101a2d] p-6';
        $innerCard = 'rounded-xl border border-[#283750] bg-[#0c1424] p-4';
        $muted = 'text-[#a0aec5]';
        $heading = 'text-[#f4f7ff]';
        $blue = 'text-[#87beff]';
        $btnGhost = 'rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:bg-[#1e2f4d]';
    @endphp

    {{-- Page heading --}}
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">
            Cinebook Downtown / Reservations / Bookings /
            {{ $booking->booking_reference }}
        </p>

        <h1 class="mt-2 text-[32px] font-bold leading-tight text-[#f4f7ff]">
            Cancel &amp; refund booking
        </h1>

        <p class="mt-2 text-sm text-[#a0aec5]">
            Review the booking details and confirm the cancellation and full refund.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-500/30 bg-green-500/10 p-4 text-sm text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-400/30 bg-red-500/10 p-4 text-sm text-red-300">
            Please review the errors below before submitting.
        </div>
    @endif

    <div class="flex flex-col gap-6 xl:flex-row xl:items-start">

        {{-- LEFT: Cancellation form --}}
        <div class="flex min-w-0 flex-1 flex-col gap-5">

            <form
                id="cancel-booking-form"
                method="POST"
                action="{{ route('admin.bookings.cancel.store', $booking) }}"
                class="{{ $card }} flex flex-col gap-6"
            >
                @csrf

                {{-- Review heading --}}
                <div>
                    <h2 class="text-xl font-bold {{ $heading }}">
                        Cancellation review
                    </h2>

                    <p class="mt-1 text-sm {{ $muted }}">
                        Check the customer, admission and payment information before proceeding.
                    </p>
                </div>

                {{-- Customer details --}}
                <div class="{{ $innerCard }}">
                    <h3 class="mb-4 text-base font-semibold {{ $heading }}">
                        Customer details
                    </h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs {{ $muted }}">Customer name</p>
                            <p class="mt-1 text-sm font-medium {{ $heading }}">
                                {{ $booking->user?->name ?? 'Deleted user' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs {{ $muted }}">Email address</p>
                            <p class="mt-1 break-all text-sm font-medium {{ $heading }}">
                                {{ $booking->user?->email ?? 'Unavailable' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs {{ $muted }}">Booking reference</p>
                            <p class="mt-1 text-sm font-medium {{ $heading }}">
                                {{ $booking->booking_reference }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs {{ $muted }}">Booking status</p>
                            <p class="mt-1 text-sm font-semibold text-green-400">
                                Confirmed
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Seats and tickets --}}
                <div class="{{ $innerCard }}">
                    <h3 class="mb-4 text-base font-semibold {{ $heading }}">
                        Seats &amp; tickets
                    </h3>

                    <div class="flex flex-wrap gap-2">
                        @forelse ($seatNames as $seatName)
                            <span class="rounded-lg border border-[#347bff]/40 bg-[#172f58] px-3 py-2 text-sm font-semibold text-[#87beff]">
                                {{ $seatName }}
                            </span>
                        @empty
                            <p class="text-sm {{ $muted }}">No seat information available.</p>
                        @endforelse
                    </div>

                    <p class="mt-4 text-xs {{ $muted }}">
                        All tickets associated with this booking will be voided.
                    </p>
                </div>

                {{-- Payment verification --}}
                <div class="{{ $innerCard }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-base font-semibold {{ $heading }}">
                            Payment verification
                        </h3>

                        <span class="rounded-full border border-green-500/30 bg-green-500/10 px-3 py-1 text-xs font-semibold text-green-400">
                            Verified payment
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs {{ $muted }}">Payment provider</p>
                            <p class="mt-1 text-sm font-medium {{ $heading }}">Stripe</p>
                        </div>

                        <div>
                            <p class="text-xs {{ $muted }}">Payment status</p>
                            <p class="mt-1 text-sm font-medium text-green-400">Paid</p>
                        </div>

                        <div>
                            <p class="text-xs {{ $muted }}">Refund amount</p>
                            <p class="mt-1 text-lg font-bold {{ $heading }}">
                                {{ $refundAmount }} {{ $booking->currency }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs {{ $muted }}">Refund type</p>
                            <p class="mt-1 text-sm font-medium {{ $heading }}">
                                Full refund
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Cancellation reason --}}
                <div>
                    <label for="reason" class="mb-2 block text-sm font-semibold {{ $heading }}">
                        Cancellation reason
                        <span class="text-red-400">*</span>
                    </label>

                    <textarea
                        id="reason"
                        name="reason"
                        rows="4"
                        minlength="5"
                        maxlength="500"
                        required
                        placeholder="Enter the reason for cancelling this booking..."
                        class="w-full rounded-xl border border-[#283750] bg-[#0c1424] px-4 py-3 text-sm text-[#f4f7ff] placeholder:text-[#71819b] focus:border-[#347bff] focus:outline-none focus:ring-1 focus:ring-[#347bff]"
                    >{{ old('reason') }}</textarea>

                    @error('reason')
                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                    @enderror

                    <p class="mt-2 text-xs {{ $muted }}">
                        Required. Enter between 5 and 500 characters.
                    </p>
                </div>

                {{-- Warning --}}
                <div class="rounded-xl border border-red-400/30 bg-[#3d222e] p-4">
                    <div class="flex items-start gap-3">
                        <span class="text-lg text-red-300">!</span>

                        <div>
                            <h3 class="text-sm font-semibold text-red-200">
                                Important: This action cannot be undone
                            </h3>

                            <p class="mt-2 text-sm leading-relaxed text-red-200/80">
                                Cancelling this booking will void its tickets, release
                                the reserved seats and request a full refund through Stripe.
                                The cancellation cannot be reversed from this screen.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Confirmation --}}
                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input
                            type="checkbox"
                            name="confirm"
                            value="1"
                            required
                            @checked(old('confirm'))
                            class="mt-1 size-4 shrink-0 rounded border-[#52627c] bg-[#0c1424] text-[#347bff] focus:ring-[#347bff]"
                        >

                        <span class="text-sm leading-relaxed {{ $heading }}">
                            I understand that this booking will be cancelled, all associated
                            tickets will be voided, and a full refund will be requested.
                        </span>
                    </label>

                    @error('confirm')
                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Buttons --}}
                <div class="flex flex-col-reverse gap-3 border-t border-[#283750] pt-5 sm:flex-row sm:justify-end">
                    <a
                        href="{{ route('admin.bookings.show', $booking) }}"
                        class="{{ $btnGhost }} text-center"
                    >
                        Go back
                    </a>

                    <button
                        type="submit"
                        @disabled(! $canCancel)
                        class="rounded-lg border border-red-400/40 bg-[#7f293b] px-5 py-[13px] text-sm font-semibold text-white transition hover:bg-[#96364a] disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Confirm cancellation &amp; refund
                    </button>
                </div>

                @unless ($canCancel)
                    <p class="text-sm text-red-300">
                        This booking cannot be cancelled because its tickets have been used
                        or a cancellation refund already exists.
                    </p>
                @endunless
            </form>
        </div>

        {{-- RIGHT: Booking summary --}}
        <div class="flex w-full shrink-0 flex-col gap-5 xl:w-[310px]">

            {{-- Movie and reservation --}}
            <div class="{{ $card }}">
                <h2 class="mb-5 text-lg font-bold {{ $heading }}">
                    Reservation summary
                </h2>

                <div class="mb-5 h-[210px] overflow-hidden rounded-xl border border-[#283750] bg-[#0c1424]">
                    @if ($movie?->poster_url)
                        <img
                            src="{{ $movie->poster_url }}"
                            alt="{{ $movie->title }} poster"
                            class="size-full object-cover"
                        >
                    @else
                        <div class="flex size-full items-center justify-center text-sm {{ $muted }}">
                            No poster available
                        </div>
                    @endif
                </div>

                <h3 class="text-lg font-bold {{ $heading }}">
                    {{ $movie?->title ?? 'Movie unavailable' }}
                </h3>

                <div class="mt-4 space-y-3 text-sm {{ $muted }}">
                    <div>
                        <p class="text-xs">Date &amp; time</p>
                        <p class="mt-1 {{ $heading }}">
                            {{ $showtime->starts_at->format('D, F j, Y · H:i') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs">Screen</p>
                        <p class="mt-1 {{ $heading }}">
                            {{ $showtime->screen?->name ?? 'Unavailable' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs">Seats</p>
                        <p class="mt-1 {{ $heading }}">
                            {{ $seatNames->isNotEmpty() ? $seatNames->implode(', ') : 'No seats' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs">Booking reference</p>
                        <p class="mt-1 {{ $heading }}">
                            {{ $booking->booking_reference }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 border-t border-[#283750] pt-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm {{ $muted }}">Full refund</span>

                        <span class="text-lg font-bold text-[#87beff]">
                            {{ $refundAmount }} {{ $booking->currency }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Audit log notice --}}
            <div class="rounded-xl border border-[#347bff]/30 bg-[#172f58] p-5">
                <h3 class="text-sm font-semibold text-[#dceaff]">
                    Cancellation audit log
                </h3>

                <p class="mt-2 text-sm leading-relaxed text-[#b9d4fa]">
                    Your cancellation reason and admin account will be recorded
                    in the booking audit log for future reference.
                </p>
            </div>
        </div>
    </div>
@endsection