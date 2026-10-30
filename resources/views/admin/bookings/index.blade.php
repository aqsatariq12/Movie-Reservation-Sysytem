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
        $statusLabel = fn ($v) => ucfirst(str_replace('_', ' ', $v));
        $canView = Route::has('admin.bookings.show');
        $field = 'rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5] min-h-[76px] focus-within:border-[#347bff]';
    @endphp

    {{-- Heading --}}
    <div class="flex items-center gap-6">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Reservations</p>
            <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Bookings</h1>
        </div>
        <a href="{{ route('admin.bookings.export', request()->except('page')) }}"
            class="shrink-0 rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">
            Export CSV ↓
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-green-700 bg-green-900/40 p-4 text-sm text-green-200">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-700 bg-red-900/40 p-4 text-sm text-red-200">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Status tabs --}}
    <div class="flex flex-wrap gap-6 text-sm">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.bookings.index', [...request()->except(['page', 'tab']), 'tab' => $key]) }}"
                class="{{ $tab === $key ? 'border-b-2 border-[#347bff] text-[#87beff]' : 'text-[#a0aec5] hover:text-white' }} pb-1">
                {{ $label }} · {{ $counts[$key] }}
            </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <label class="{{ $field }} flex flex-col">
            <span class="uppercase leading-[1.7]">Reference / customer</span>
            <input type="text" name="search" value="{{ request('search') }}" maxlength="100"
                placeholder="Search reference, name or email"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]">
        </label>

        <label class="{{ $field }} flex flex-col">
            <span class="uppercase leading-[1.7]">Status</span>
            <select name="status" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                        {{ $statusLabel($status->value) }}
                    </option>
                @endforeach
            </select>
        </label>

        <div class="{{ $field }}">
            <p class="uppercase leading-[1.7]">Date range</p>
            <div class="mt-1 flex items-center gap-2">
                <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()"
                    class="w-full min-w-0 bg-transparent text-[#f4f7ff] outline-none [color-scheme:dark]">
                <span>→</span>
                <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()"
                    class="w-full min-w-0 bg-transparent text-[#f4f7ff] outline-none [color-scheme:dark]">
            </div>
        </div>

        <label class="{{ $field }} flex flex-col">
            <span class="uppercase leading-[1.7]">Movie</span>
            <select name="movie_id" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                <option value="">All movies</option>
                @foreach ($movies as $movie)
                    <option value="{{ $movie->id }}" @selected((int) request('movie_id') === $movie->id)>{{ $movie->title }}</option>
                @endforeach
            </select>
        </label>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
        <table class="w-full min-w-[860px] text-left text-xs text-[#f4f7ff]">
            <thead class="text-[#a0aec5]">
                <tr class="uppercase">
                    <th class="pb-5 font-normal text-[#f4f7ff]">Reference / Customer</th>
                    <th class="pb-5 font-normal">Movie / Show</th>
                    <th class="pb-5 font-normal">Seats</th>
                    <th class="pb-5 font-normal">Total</th>
                    <th class="pb-5 font-normal text-[#87beff]">Status</th>
                    <th class="pb-5 font-normal">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    @php $state = $booking->adminState(); @endphp
                    <tr class="border-t border-[#283750]">
                        <td class="py-4 pr-3 whitespace-nowrap">
                            {{ $booking->booking_reference }} / {{ $booking->user?->name ?? 'Deleted user' }}
                        </td>
                        <td class="py-4 pr-3 whitespace-nowrap text-[#a0aec5]">
                            {{ $booking->showtime?->movie?->title }} / {{ $booking->showtime?->starts_at?->format('M j · H:i') }}
                        </td>
                        <td class="py-4 pr-3 whitespace-nowrap text-[#a0aec5]">{{ $booking->seat_summary ?: '—' }}</td>
                        <td class="py-4 pr-3 whitespace-nowrap text-[#a0aec5]">{{ \App\Models\Showtime::money($booking->total_amount_cents) }}</td>
                        <td class="py-4 pr-3 whitespace-nowrap {{ $tones[$state['tone']] ?? 'text-[#a0aec5]' }}">{{ $state['label'] }}</td>
                        <td class="py-4 whitespace-nowrap">
                            @if ($canView)
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="text-[#a0aec5] hover:text-white hover:underline">View →</a>
                            @else
                                <span class="cursor-not-allowed text-[#a0aec5]/50">View →</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-[#a0aec5]">No bookings match these filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="flex items-center justify-between gap-4">
        <p class="text-xs text-[#a0aec5]">
            Showing {{ $bookings->firstItem() ?? 0 }}–{{ $bookings->lastItem() ?? 0 }} of {{ $bookings->total() }}
            {{ \Illuminate\Support\Str::plural('booking', $bookings->total()) }}
        </p>
        <div class="flex items-center gap-4 text-sm">
            @if ($bookings->previousPageUrl())
                <a href="{{ $bookings->previousPageUrl() }}"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">← Previous</a>
            @else
                <span class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white opacity-40">← Previous</span>
            @endif

            <span class="text-xs text-[#87beff]">• {{ $bookings->currentPage() }}</span>

            @if ($bookings->nextPageUrl())
                <a href="{{ $bookings->nextPageUrl() }}"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">Next →</a>
            @else
                <span class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white opacity-40">Next →</span>
            @endif
        </div>
    </div>

    <p class="text-xs leading-[1.45] text-[#a0aec5]">Amounts in USD · Stripe TEST MODE · Export respects the active filters.</p>
@endsection