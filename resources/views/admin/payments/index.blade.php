@extends('layouts.admin')

@section('content')
    @php
        $keep = request()->except(['tab', 'status', 'page']);
        $stateLabels = ['paid' => 'Succeeded', 'pending' => 'Processing'];
        $tones = ['brand' => 'text-[#347bff]', 'muted' => 'text-[#a0aec5]'];
    @endphp

    {{-- Heading --}}
    <div class="flex items-center gap-6">
        <div class="flex-1">
            <p class="text-xs font-semibold uppercase text-[#347bff]">Cinebook Downtown / Reservations</p>
            <h1 class="text-[32px] font-bold leading-normal text-[#f4f7ff]">Payments</h1>
        </div>
        <a href="{{ route('admin.payments.export', request()->except('page')) }}"
            class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white whitespace-nowrap hover:bg-[#2f6fe6]">
            Export CSV ↓
        </a>
    </div>

    {{-- Notice --}}
    <div class="rounded-lg border border-[#347bff]/30 bg-[#347bff]/10 p-4 text-sm leading-[1.6] text-[#f4f7ff]">
        <p>ⓘ Read-only · Stripe TEST MODE</p>
        <p>Payment records are received from verified Stripe events. No manual paid toggle, editing or card data is
            available.</p>
    </div>

    {{-- Tabs --}}
    <nav class="flex gap-6 text-sm text-[#a0aec5]">
        @foreach ($tabs as $key => $tabLabel)
            <a href="{{ route('admin.payments.index', $key === 'all' ? $keep : $keep + ['tab' => $key]) }}"
                class="py-1.5 {{ $tab === $key ? 'text-[#347bff] underline underline-offset-4' : 'hover:text-white' }}">
                {{ $tabLabel }}
            </a>
        @endforeach
    </nav>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.payments.index') }}" class="grid grid-cols-3 gap-4">
        @if ($tab !== 'all')
            <input type="hidden" name="tab" value="{{ $tab }}">
        @endif

        <label class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-3.5">
            <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Reference or booking</span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search payment or booking…"
                class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]">
        </label>

        <div class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-3.5">
            <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Date range</span>
            <div class="flex items-center gap-2">
                <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()"
                    aria-label="From date"
                    class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none [color-scheme:dark]">
                <span class="text-[13px] text-[#a0aec5]">–</span>
                <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()" aria-label="To date"
                    class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none [color-scheme:dark]">
            </div>
        </div>

        <label class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-3.5">
            <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Payment state</span>
            <select name="status" onchange="this.form.submit()"
                class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none">
                <option value="" class="bg-[#0c1424]">All states</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" class="bg-[#0c1424]" @selected(request('status') === $status->value)>
                        {{ $stateLabels[$status->value] ?? ucfirst($status->value) }}
                    </option>
                @endforeach
            </select>
        </label>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-[#283750] bg-[#0c1424] p-4">
        <table class="w-full min-w-[900px] table-fixed text-sm">
            <thead>
                <tr class="border-b border-[#283750] text-left text-xs font-semibold uppercase text-[#a0aec5]">
                    <th class="pb-3 pr-3">Payment / Booking</th>
                    <th class="pb-3 pr-3">Customer</th>
                    <th class="pb-3 pr-3">Amount</th>
                    <th class="pb-3 pr-3">Status</th>
                    <th class="pb-3 pr-3">Updated</th>
                    <th class="pb-3">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    @php
                        $state = $payment->adminState();
                        $when = $payment->paid_at ?? $payment->created_at;
                        $reference = 'PAY-' . str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT)
                            . ' / ' . ($payment->booking?->booking_reference ?? '—');
                    @endphp
                    <tr class="border-b border-[#283750] last:border-b-0">
                        <td class="truncate py-3.5 pr-3 text-[#f4f7ff]" title="{{ $reference }}">{{ $reference }}</td>
                        <td class="truncate py-3.5 pr-3 text-[#a0aec5]">{{ $payment->booking?->user?->name ?? '—' }}</td>
                        <td class="py-3.5 pr-3 text-[#a0aec5]">
                            {{ $payment->currency === 'USD' ? '$' : $payment->currency . ' ' }}{{ number_format($payment->amount_cents / 100, 2) }}
                        </td>
                        <td class="truncate py-3.5 pr-3 {{ $tones[$state['tone']] }}">{{ $state['label'] }}</td>
                        <td class="py-3.5 pr-3 text-[#a0aec5]">{{ $when?->format('M j · H:i') }}</td>
                        <td class="py-3.5">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="text-[#347bff] hover:text-white">View
                                →</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-[#a0aec5]">No payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination (same component as the Movies page) --}}
    {{ $payments->links('vendor.pagination.cinebook') }}
@endsection