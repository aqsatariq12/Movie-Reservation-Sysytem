{{-- Poster card used in "Now showing". Expects the keys returned by GetHomePageData::nowShowing(). --}}
@props(['movie'])

<article class="flex flex-col gap-3">
    <a href="{{ $movie['url'] }}">
        <img
            src="{{ asset($movie['poster']) }}"
            alt="{{ $movie['title'] }} poster"
            loading="lazy"
            class="aspect-[243/321] w-full rounded-2xl object-cover"
        >
    </a>

    <div class="flex flex-col gap-[1.6em] text-[14px] leading-[1.6] text-cb-muted">
        <p>{{ $movie['title'] }}</p>
        <p>{{ $movie['rating'] }} · {{ $movie['duration'] }} min · {{ implode(' · ', $movie['genres']) }}</p>
        <p>{{ $movie['next_showtimes'] }}</p>
    </div>
</article>
