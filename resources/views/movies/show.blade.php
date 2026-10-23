<x-layouts.cinebook :title="$movie->title . ' — CineBook'">
    <main class="mx-auto flex max-w-[1440px] flex-col gap-8 px-4 py-10 md:px-8 xl:px-16">

        <a href="{{ route('movies.index') }}" class="text-sm text-cb-muted hover:text-cb-brand">
            ← Back to movies
        </a>

        <section class="grid gap-8 md:grid-cols-[280px_1fr] lg:grid-cols-[340px_1fr]">

            <div>
                <img src="{{ $movie->posterUrl ?? asset('images/cinebook/movie-1.png') }}"
                    alt="{{ $movie->title }} poster" class="w-full rounded-2xl object-cover shadow-md">
            </div>

            <div class="flex flex-col gap-5">
                <p class="text-sm font-medium uppercase tracking-wider text-cb-brand">
                    {{ str_replace('_', ' ', $movie->status->value) }}
                </p>

                <h1 class="text-4xl font-bold text-cb-ink md:text-5xl">
                    {{ $movie->title }}
                </h1>

                <p class="text-sm text-cb-muted">
                    {{ $movie->release_date?->format('M j, Y') }}
                    · {{ $movie->rating }}
                    · {{ $movie->duration_minutes }} min
                </p>

                <div class="flex flex-wrap gap-2">
                    @foreach ($movie->genres as $genre)
                        <span class="rounded-full border border-cb-line px-3 py-1 text-sm text-cb-muted">
                            {{ $genre->name }}
                        </span>
                    @endforeach
                </div>

                <p class="leading-7 text-cb-muted">
                    {{ $movie->synopsis ?: 'More information about this movie will be available soon.' }}
                </p>

                @if ($movie->trailer_url)
                    <a href="{{ $movie->trailer_url }}" target="_blank" rel="noopener noreferrer"
                        class="text-sm font-medium text-cb-brand hover:underline">
                        Watch trailer →
                    </a>
                @endif
            </div>
        </section>

        <section class="flex flex-col gap-5" id="showtimes">
            <h2 class="text-2xl font-bold text-cb-ink">
                Available showtimes
            </h2>


            @forelse ($showtimes as $item)
                @php
                    $showtime = $item['showtime'];
                @endphp

                <div
                    class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-cb-line bg-white p-5">
                    <div>
                        <p class="font-semibold text-cb-ink">
                            {{ $showtime->screen->name }}
                        </p>

                        <p class="mt-1 text-sm text-cb-muted">
                            {{ $showtime->starts_at->format('D, M j, Y · H:i') }}
                        </p>

                        <p class="mt-1 text-sm text-cb-muted">
                            From ${{ number_format($showtime->base_price_cents / 100, 2) }}
                        </p>

                        @if ($item['booking_open'])
                            <p class="mt-2 text-sm text-green-700">
                                Booking open
                            </p>
                        @else
                            <!-- <p class="mt-2 text-sm text-red-600">
                                Booking closed — sales close 15 minutes before the show
                            </p> -->

                            <p class="text-[#a0aec5]">Sales close · {{ $salesCutoff }} minutes before start</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm text-cb-brand">
                            {{ $showtime->status->value }}
                        </span>

                        @if ($item['booking_open'])
                            <a href="{{ route('showtimes.seats', $showtime) }}"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                Choose seats →
                            </a>
                        @else
                            <span
                                class="cursor-not-allowed rounded-xl bg-slate-100 px-5 py-3 text-sm font-medium text-slate-400">
                                Booking closed
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="rounded-2xl border border-cb-line p-5 text-cb-muted">
                    No upcoming showtimes are available for this movie yet.
                </p>
            @endforelse

        </section>

    </main>
</x-layouts.cinebook>