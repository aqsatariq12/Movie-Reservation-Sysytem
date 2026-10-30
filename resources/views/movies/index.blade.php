<x-layouts.cinebook title="Movies — CineBook Downtown">
    <div class="mx-auto max-w-[1440px] px-4 py-10 md:px-8 xl:px-16">

        {{-- Page heading --}}
        <div class="mb-7">
            <p class="mb-3 text-[14px] text-cb-muted">
                MOVIES / CINEBOOK DOWNTOWN
            </p>

            <h1 class="mb-3 text-[32px] font-bold leading-tight text-cb-ink md:text-[38px]">
                Find your next escape
            </h1>

            <p class="mb-5 text-[14px] leading-relaxed text-cb-muted">
                Discover what’s playing and what’s next. All showtimes are local to Downtown.
            </p>

            <div class="flex flex-wrap gap-5 text-[14px]">
    <a href="{{ route('movies.index', array_filter([
        'tab' => 'now-showing',
        'search' => $search,
        'genre' => $genreSlug,
        'show_date' => $showDate,
    ])) }}"
       class="border-b-2 pb-2 font-semibold
       {{ $tab === 'now-showing'
            ? 'border-blue-500 text-blue-500'
            : 'border-transparent text-cb-muted hover:text-cb-ink' }}">
        Now showing
    </a>

    <a href="{{ route('movies.index', array_filter([
        'tab' => 'coming-soon',
        'search' => $search,
        'genre' => $genreSlug,
        'show_date' => $showDate,
    ])) }}"
       class="border-b-2 pb-2 font-semibold
       {{ $tab === 'coming-soon'
            ? 'border-blue-500 text-blue-500'
            : 'border-transparent text-cb-muted hover:text-cb-ink' }}">
        Coming soon
    </a>
</div>
        </div>

        {{-- Search and filters: visual layout for now --}}

        {{-- Search and filters --}}
        <form method="GET"
              action="{{ route('movies.index') }}"
              class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

            <label class="rounded-2xl border border-cb-line bg-white p-4">
                <span class="mb-2 block text-[12px] font-semibold text-cb-muted">
                    SEARCH MOVIES
                </span>

                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search by title..."
                    class="w-full bg-transparent text-[14px] text-cb-ink outline-none"
                >

                <input type="hidden" name="tab" value="{{ $tab }}">
            </label>

            <label class="rounded-2xl border border-cb-line bg-white p-4">
                <span class="mb-2 block text-[12px] font-semibold text-cb-muted">
                    GENRE
                </span>

                <select
                    name="genre"
                    onchange="this.form.submit()"
                    class="w-full bg-transparent text-[14px] text-cb-ink outline-none"
                >
                    <option value="">All genres</option>

                    @foreach ($genres as $genre)
                        <option
                            value="{{ $genre->slug }}"
                            @selected($genreSlug === $genre->slug)
                        >
                            {{ $genre->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            
<label class="rounded-2xl border border-cb-line bg-white p-4 sm:col-span-2 lg:col-span-1">
    <span class="mb-2 block text-[12px] font-semibold text-cb-muted">
        SHOW DATE
    </span>

    <input
        type="date"
        name="show_date"
        value="{{ $showDate ?? '' }}"
        min="{{ now()->toDateString() }}"
        class="w-full bg-transparent text-[14px] text-cb-ink outline-none"
    >
</label>


            <button
                type="submit"
                class="rounded-xl bg-blue-500 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-600 sm:col-span-2 lg:col-span-3"
            >
                Search movies
            </button>
        </form>


        <p class="mb-6 text-[13px] text-cb-muted">
            {{ count($movies) }} movies
        </p>

        {{-- Movie poster grid --}}
        <div class="grid grid-cols-1 gap-x-7 gap-y-9 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($movies as $movie)
                <article class="min-w-0">
                    <a href="{{ route('movies.show', $movie) }}"
                       class="mb-3 block overflow-hidden rounded-2xl bg-white"
                       aria-label="View {{ $movie->title }} details">

                        @if ($movie->posterUrl)
                            <img
                                src="{{ $movie->posterUrl }}"
                                alt="{{ $movie->title }} poster"
                                class="aspect-[306/404] w-full object-cover transition duration-300 hover:scale-[1.02]"
                                loading="lazy"
                            >
                        @else
                            <div class="flex aspect-[306/404] items-center justify-center bg-stone-200 p-5 text-center text-cb-muted">
                                {{ $movie->title }}
                            </div>
                        @endif
                    </a>

                    <h2 class="mb-1 text-[17px] font-semibold text-cb-ink">
                        <a href="{{ route('movies.show', $movie) }}"
                           class="hover:text-blue-500">
                            {{ $movie->title }}
                        </a>
                    </h2>

                    <p class="mb-2 text-[13px] leading-relaxed text-cb-muted">
                        {{ $movie->rating }}
                        · {{ $movie->duration_minutes }} min
                        @if ($movie->genres->isNotEmpty())
                            · {{ $movie->genres->pluck('name')->join(' · ') }}
                        @endif
                    </p>

                    <a href="{{ route('movies.show', $movie) }}"
                       class="text-[13px] font-medium text-blue-500 hover:underline">
                        View movie and showtimes →
                    </a>
                </article>
            @empty
               <p class="text-[14px] text-cb-muted">
    No movies found. Try another search or genre.
</p>
            @endforelse
        </div>

    </div>
</x-layouts.cinebook>