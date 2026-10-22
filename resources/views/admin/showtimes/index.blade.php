@extends('layouts.admin')

@section('content')
    <div class="flex items-center gap-6">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Programming</p>
            <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">The schedule</h1>
        </div>
        <a href="{{ route('admin.showtimes.create') }}"
            class="shrink-0 rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">
            + Create showtime
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

    <form method="GET" action="{{ route('admin.showtimes.index') }}" class="grid gap-6 md:grid-cols-3">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <p class="uppercase">Date range</p>
            <div class="mt-1 flex items-center gap-2">
                <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()"
                    class="w-full bg-transparent text-[#f4f7ff] outline-none [color-scheme:dark]">
                <span>→</span>
                <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()"
                    class="w-full bg-transparent text-[#f4f7ff] outline-none [color-scheme:dark]">
            </div>
        </div>

        <div class="rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <p class="uppercase">Screen</p>
            <select name="screen_id" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                <option value="">All screens</option>
                @foreach ($screens as $screen)
                    <option value="{{ $screen->id }}" @selected((int) request('screen_id') === $screen->id)>{{ $screen->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <p class="uppercase">Movie</p>
            <select name="movie_id" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                <option value="">All movies</option>
                @foreach ($movies as $movie)
                    <option value="{{ $movie->id }}" @selected((int) request('movie_id') === $movie->id)>{{ $movie->title }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="flex gap-6 text-sm">
        @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled'] as $key => $label)
            <a href="{{ route('admin.showtimes.index', [...request()->except(['page', 'tab']), 'tab' => $key]) }}"
                class="{{ $tab === $key ? 'border-b-2 border-[#347bff] text-[#87beff]' : 'text-[#a0aec5] hover:text-white' }} pb-1">
                {{ $label }} · {{ $counts[$key] }}
            </a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
        <table class="w-full min-w-[760px] text-left text-[13px] text-[#f4f7ff]">
            <thead class="text-[#a0aec5]">
                <tr class="uppercase">
                    <th class="pb-5 font-normal">Movie / Screen</th>
                    <th class="pb-5 font-normal">Start → End¹</th>
                    <th class="pb-5 font-normal">Base / Premium</th>
                    <th class="pb-5 font-normal">Sold / Held</th>
                    <th class="pb-5 font-normal text-[#87beff]">Status</th>
                    <th class="pb-5 font-normal">Manage</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($showtimes as $s)
                    <tr class="border-t border-[#283750]">
                        <td class="py-4">{{ $s->movie_title }} · {{ $s->screen->name }}</td>
                        <td class="py-4">{{ $s->starts_at->format('M j · H:i') }} → {{ $s->ends_at->format('H:i') }}</td>
                        <td class="py-4">{{ \App\Models\Showtime::money($s->base_price_cents) }} /
                            {{ \App\Models\Showtime::money($s->base_price_cents + $surcharge) }}
                        </td>
                        <td class="py-4">{{ $s->sold_count }} / {{ $s->held_count }}</td>
                        <td class="py-4 {{ $s->status->value === 'cancelled' ? 'text-red-400' : 'text-[#87beff]' }}">
                            {{ ucfirst($s->status->value) }}
                        </td>
                        <td class="py-4">
                            @if ($tab === 'upcoming' && $s->status->value === 'scheduled')
                                {{-- No sold/held seats = Edit. Booked = View (edit page opens with locked fields). --}}
                                <a href="{{ route('admin.showtimes.edit', $s) }}" class="text-[#87beff] hover:underline">
                                    {{ ($s->sold_count + $s->held_count) > 0 ? 'View' : 'Edit' }} →
                                </a>
                            @else
                                {{-- Past / cancelled: read-only panel below --}}
                                <a href="{{ route('admin.showtimes.index', [...request()->except(['view']), 'view' => $s->id]) }}#showtime-panel"
                                    class="text-[#a0aec5] hover:text-white hover:underline">View →</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-[#a0aec5]">No showtimes here yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-[#a0aec5]">¹ End includes the movie duration +
        {{ \App\Models\Showtime::TURNAROUND_MINUTES }}-minute turnaround buffer. Showtimes cannot overlap within a screen.
    </p>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xs text-[#a0aec5]">Showing {{ $showtimes->firstItem() ?? 0 }}–{{ $showtimes->lastItem() ?? 0 }} of
            {{ $showtimes->total() }} {{ $tab }} showtimes
        </p>
        <div class="flex items-center gap-4 text-sm">
            @if ($showtimes->previousPageUrl())
                <a href="{{ $showtimes->previousPageUrl() }}"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">←
                    Previous</a>
            @endif
            <span class="text-[#a0aec5]">• {{ $showtimes->currentPage() }}</span>
            @if ($showtimes->nextPageUrl())
                <a href="{{ $showtimes->nextPageUrl() }}"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">Next
                    →</a>
            @endif
        </div>
    </div>

    <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
        <p>ⓘ Booked shows are protected</p>
        <p>Once bookings exist, movie, screen and start time are locked. Cancel the show with a reason and create a
            replacement; never delete a booked show.</p>
    </div>
        @if ($viewing)
        <div id="showtime-panel" class="flex flex-col gap-5 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6">
            <div class="flex items-center gap-4">
                <p class="flex-1 text-xs uppercase text-[#a0aec5]">
                    • {{ $viewing->status->value === 'cancelled' ? 'Cancelled' : 'Past' }} · Read-only
                </p>
                <p class="flex-1 text-xl font-bold leading-[1.13] text-[#f4f7ff]">
                    {{ $viewing->movie->title }} · {{ $viewing->starts_at->format('M j · H:i') }} · {{ $viewing->screen->name }}
                </p>
            </div>
            <p class="text-[13px] leading-[1.45] text-[#a0aec5]">
                Historical scheduling and booking records are view-only. Editing and cancellation are unavailable.
            </p>
        </div>
    @endif

@endsection