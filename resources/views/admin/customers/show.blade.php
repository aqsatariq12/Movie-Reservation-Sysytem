@extends('layouts.admin')

@section('content')
<div>
    <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">
        Cinebook Downtown / People /
        <a href="{{ route('admin.customers.index') }}" class="hover:underline">Customers</a>
    </p>
    <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">{{ $customer->name }}</h1>
</div>

<div class="grid items-center gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">

    {{-- Profile card --}}
    <aside class="flex flex-col gap-4 rounded-2xl border border-[#283750] bg-[#101a2d] p-6 text-sm">
        <p class="text-xs text-[#a0aec5]">• {{ $customer->initials() }}</p>
        <h2 class="text-2xl font-bold text-[#f4f7ff]">Customer profile</h2>

        <p class="break-all text-[#a0aec5]">{{ $customer->email }}</p>
        <p class="text-[#a0aec5]">
            {{ $customer->email_verified_at ? '✓ Email verified' : 'Email not verified' }}
        </p>
        <p class="text-[#a0aec5]">Role · {{ ucfirst($customer->role->value) }}</p>
        <p class="text-[#a0aec5]">Bookings · {{ $customer->bookings_count }}</p>
        <p class="text-[#a0aec5]">
            Total spent · net · {{ \App\Models\Showtime::money($netSpentCents) }} USD
        </p>
        <p class="text-[#a0aec5]">Read-only profile · No deletion</p>
    </aside>

    {{-- Booking history --}}
    <section class="flex min-w-0 flex-col gap-4">
        <h2 class="text-2xl font-bold text-[#f4f7ff]">Booking history</h2>

        <div class="max-h-[480px] overflow-auto rounded-2xl border border-[#283750] bg-[#101a2d]">
            <table class="w-full min-w-[640px] text-left text-[13px] text-[#f4f7ff]">
                <thead class="text-[#a0aec5]">
                    <tr class="uppercase">
                        <th class="sticky top-0 z-10 bg-[#101a2d] px-4 py-4 font-normal">Reference / Movie</th>
                        <th class="sticky top-0 z-10 bg-[#101a2d] px-4 py-4 font-normal">Date / Show</th>
                        <th class="sticky top-0 z-10 bg-[#101a2d] px-4 py-4 font-normal">Amount</th>
                        <th class="sticky top-0 z-10 bg-[#101a2d] px-4 py-4 font-normal">State</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                    @php($state = $booking->adminState())
                    <tr class="border-t border-[#283750]">
                        <td class="max-w-[240px] truncate px-4 py-4">
                            CB-…{{ \Illuminate\Support\Str::substr($booking->booking_reference, -4) }}
                            / {{ $booking->showtime?->movie?->title ?? '—' }}
                        </td>
                        <td class="px-4 py-4 text-[#a0aec5]">
                            {{ $booking->showtime?->starts_at?->format('M j · H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-4 text-[#a0aec5]">
                            {{ \App\Models\Showtime::money($booking->total_amount_cents) }}
                        </td>
                        <td class="px-4 py-4">
                            <x-admin.status-badge :tone="$state['tone']">{{ $state['label'] }}</x-admin.status-badge>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-[#a0aec5]">No bookings yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between gap-4 text-sm">
            <p class="text-xs text-[#a0aec5]">
                Showing {{ $bookings->firstItem() ?? 0 }}–{{ $bookings->lastItem() ?? 0 }} of {{ $bookings->total() }}
                bookings
            </p>

            @if ($bookings->hasPages())
                <div class="flex items-center gap-3">
                    @if ($bookings->previousPageUrl())
                        <a href="{{ $bookings->previousPageUrl() }}"
                            class="rounded-lg border border-[#283750] bg-[#17243b] px-4 py-2 text-white hover:bg-[#1e2f4d]">←
                            Previous</a>
                    @endif
                    @if ($bookings->nextPageUrl())
                        <a href="{{ $bookings->nextPageUrl() }}"
                            class="rounded-lg border border-[#283750] bg-[#17243b] px-4 py-2 text-white hover:bg-[#1e2f4d]">Next
                            →</a>
                    @endif
                </div>
            @endif
        </div>
        <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4 text-sm text-[#f4f7ff]">
            <p class="font-medium">ⓘ Role changes are controlled separately</p>
            <p class="mt-1 text-[#a0aec5]">Staff and administrator assignments belong in Staff &amp; admins and are
                always audited.</p>
        </div>
    </section>
</div>
@endsection