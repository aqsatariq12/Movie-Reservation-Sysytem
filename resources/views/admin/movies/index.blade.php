@extends('layouts.admin')

@section('content')
    @php
        $label = fn ($value) => ucfirst(str_replace('_', ' ', $value));
        $keep  = request()->except(['status', 'page']);
        $canCreate = Route::has('admin.movies.create');
    @endphp

    {{-- Heading --}}
    <div class="flex items-center gap-6">
        <div class="flex-1">
            <p class="text-xs font-semibold uppercase text-[#347bff]">Cinebook Downtown / Programming</p>
            <h1 class="text-[32px] font-bold leading-normal text-[#f4f7ff]">Movie library</h1>
        </div>
        <a href="{{ $canCreate ? route('admin.movies.create') : '#' }}"
           class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white whitespace-nowrap">
            + Add movie
        </a>
    </div>

    {{-- Status tabs --}}
    <nav class="flex gap-6 text-sm text-[#a0aec5]">
        <a href="{{ route('admin.movies.index', $keep) }}"
           class="py-1.5 {{ ! $activeStatus ? 'text-[#347bff] underline underline-offset-4' : 'hover:text-white' }}">
            All movies · {{ $totalCount }}
        </a>
        @foreach ($statuses as $status)
            <a href="{{ route('admin.movies.index', $keep + ['status' => $status->value]) }}"
               class="py-1.5 {{ $activeStatus === $status ? 'text-[#347bff] underline underline-offset-4' : 'hover:text-white' }}">
                {{ $label($status->value) }} · {{ $statusCounts[$status->value] ?? 0 }}
            </a>
        @endforeach
    </nav>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.movies.index') }}" class="grid grid-cols-3 gap-4">
        <label class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-3.5">
            <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Title search</span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search movie title…"
                   class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]">
        </label>

        <label class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-3.5">
            <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Genre</span>
            <select name="genre" onchange="this.form.submit()"
                    class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none">
                <option value="" class="bg-[#0c1424]">All genres</option>
                @foreach ($genres as $genre)
                    <option value="{{ $genre->slug }}" class="bg-[#0c1424]" @selected(request('genre') === $genre->slug)>
                        {{ $genre->name }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-3.5">
            <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Status</span>
            <select name="status" onchange="this.form.submit()"
                    class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none">
                <option value="" class="bg-[#0c1424]">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" class="bg-[#0c1424]" @selected($activeStatus === $status)>
                        {{ $label($status->value) }}
                    </option>
                @endforeach
            </select>
        </label>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-[#283750] bg-[#0c1424] p-4">
        <table class="w-full text-xs">
            <thead>
                <tr class="text-left uppercase">
                    <th class="h-[58px] w-[245px] pr-3 font-normal text-[#f4f7ff]">Movie title</th>
                    <th class="h-[58px] w-[180px] pr-3 font-normal text-[#a0aec5]">Genres</th>
                    <th class="h-[58px] w-[150px] pr-3 font-normal text-[#a0aec5]">Duration / Rating</th>
                    <th class="h-[58px] w-[150px] pr-3 font-normal text-[#a0aec5]">Release</th>
                    <th class="h-[58px] w-[175px] pr-3 font-normal text-[#347bff]">Status</th>
                    <th class="h-[58px] w-[110px] font-normal text-[#a0aec5]">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movies as $movie)
                    <tr class="whitespace-nowrap">
                        <td class="h-[58px] pr-3 text-[#f4f7ff]">{{ $movie->title }}</td>
                        <td class="h-[58px] pr-3 text-[#a0aec5]">{{ $movie->genres->pluck('name')->implode(' · ') }}</td>
                        <td class="h-[58px] pr-3 text-[#a0aec5]">{{ $movie->duration_minutes }} min / {{ $movie->rating }}</td>
                        <td class="h-[58px] pr-3 text-[#a0aec5]">{{ $movie->release_date?->format('M j, Y') }}</td>
                        <td class="h-[58px] pr-3 {{ $movie->status->value === 'archived' ? 'text-[#a0aec5]' : 'text-[#347bff]' }}">
                            {{ $label($movie->status->value) }}
                        </td>
                        <td class="h-[58px]">
                            <a href="{{ Route::has('admin.movies.edit') ? route('admin.movies.edit', $movie) : '#' }}"
                               class="text-[#a0aec5] hover:text-white">Edit →</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-[#a0aec5]">No movies found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    {{ $movies->links('vendor.pagination.cinebook') }}

    {{-- Notice --}}
    <div class="rounded-lg border border-[#347bff]/30 bg-[#347bff]/10 p-4 text-sm leading-[1.6] text-[#f4f7ff]">
        <p>ⓘ Preserve show history</p>
        <p>Movies with showtimes are archived, not deleted. Archived movies disappear from new public discovery, while booking history remains intact.</p>
    </div>

    {{-- Featured strip --}}
    @if ($featured)
        <div class="flex items-center gap-4 text-sm leading-[1.6] text-[#a0aec5]">
            @if ($featured->poster_url)
                <img src="{{ $featured->poster_url }}" alt="{{ $featured->title }}"
                     class="h-[86px] w-16 shrink-0 rounded-[10px] object-cover">
            @else
                <div class="h-[86px] w-16 shrink-0 rounded-[10px] bg-[#283750]"></div>
            @endif
            <div>
                <p>{{ $featured->title }}</p>
                <p class="mt-3">
                    Featured on the customer home · {{ $featured->showtimes_count }} showtimes ·
                    {{ $featured->poster_url ? 'Poster ready' : 'No poster' }}
                </p>
            </div>
        </div>
    @endif
@endsection