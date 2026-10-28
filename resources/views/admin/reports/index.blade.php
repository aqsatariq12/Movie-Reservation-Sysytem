@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (int $cents) => ($cents < 0 ? '-' : '') . '$' . number_format(abs($cents) / 100, 2);
        $fmtShort = fn (int $cents) => ($cents < 0 ? '-' : '') . '$' . number_format(abs($cents) / 100);
        $query = request()->except(['page', 'tab']);
    @endphp

    <div class="flex items-center gap-6">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Operations</p>
            <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Sales reports</h1>
        </div>
        <a href="{{ route('admin.reports.export', [...$query, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'tab' => $tab]) }}"
            class="shrink-0 rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">
            Export CSV ↓
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-700 bg-red-900/40 p-4 text-sm text-red-200">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-4 md:grid-cols-3">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">From</span>
            <input type="date" name="from" value="{{ $from->toDateString() }}" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [color-scheme:dark]">
        </label>

        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">To</span>
            <input type="date" name="to" value="{{ $to->toDateString() }}" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [color-scheme:dark]">
        </label>

        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Movie</span>
            <select name="movie_id" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                <option value="">All movies</option>
                @foreach ($movies as $movie)
                    <option value="{{ $movie->id }}" @selected((int) request('movie_id') === $movie->id)>{{ $movie->title }}</option>
                @endforeach
            </select>
        </label>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Tickets', number_format($totals['tickets'])],
            ['Gross', $fmtShort($totals['gross'])],
            ['Refunds', $fmtShort($totals['refunds'])],
            ['Net', $fmtShort($totals['net'])],
        ] as [$label, $value])
            <div class="rounded-[14px] border border-[#283750] bg-[#101a2d] p-6">
                <p class="text-sm leading-[1.6] text-[#a0aec5]">{{ $label }}</p>
                <p class="mt-3 text-[32px] font-bold leading-[1.2] text-[#f4f7ff]">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex gap-6 text-sm">
        @foreach (['day' => 'Per day', 'movie' => 'Per movie'] as $key => $label)
            <a href="{{ route('admin.reports.index', [...$query, 'tab' => $key]) }}"
                class="{{ $tab === $key ? 'border-b-2 border-[#347bff] text-[#87beff]' : 'text-[#a0aec5] hover:text-white' }} pb-1">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
        <table class="w-full min-w-[760px] text-left text-[13px] text-[#f4f7ff]">
            <thead class="text-[#a0aec5]">
                <tr class="uppercase">
                    <th class="pb-5 font-normal">{{ $tab === 'movie' ? 'Movie' : 'Date' }}</th>
                    <th class="pb-5 font-normal">Tickets</th>
                    <th class="pb-5 font-normal">Gross USD</th>
                    <th class="pb-5 font-normal">Refunds USD</th>
                    <th class="pb-5 font-normal text-[#87beff]">Net USD</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-t border-[#283750]">
                        <td class="py-4">{{ $row['label'] }}</td>
                        <td class="py-4 text-[#a0aec5]">{{ number_format($row['tickets']) }}</td>
                        <td class="py-4 text-[#a0aec5]">{{ $fmt($row['gross']) }}</td>
                        <td class="py-4 text-[#a0aec5]">{{ $fmt($row['refunds']) }}</td>
                        <td class="py-4">{{ $fmt($row['net']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-[#a0aec5]">No sales in this date range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-[#a0aec5]">
        Verified sales and succeeded refunds in the selected date range. All amounts are test-mode USD.
    </p>

    <div class="flex items-center justify-between gap-4 text-sm">
        <p class="text-xs text-[#a0aec5]">
            Showing {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} of
            {{ $rows->total() }} {{ $tab === 'movie' ? 'movies' : 'days' }}
        </p>

        <div class="flex items-center gap-4">
            @if ($rows->previousPageUrl())
                <a href="{{ $rows->previousPageUrl() }}"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">← Previous</a>
            @else
                <span class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b]/50 px-5 py-[13px] text-white/40">← Previous</span>
            @endif

            <span class="text-xs text-[#87beff]">• {{ $rows->currentPage() }} / {{ $rows->lastPage() }}</span>

            @if ($rows->nextPageUrl())
                <a href="{{ $rows->nextPageUrl() }}"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">Next →</a>
            @else
                <span class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b]/50 px-5 py-[13px] text-white/40">Next →</span>
            @endif
        </div>
    </div>
@endsection