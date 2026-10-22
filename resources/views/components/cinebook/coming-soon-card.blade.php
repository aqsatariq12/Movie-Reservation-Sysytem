{{-- Card used in "Coming soon". Expects the keys returned by GetHomePageData::comingSoon(). --}}
@props(['movie'])

<article class="flex items-center gap-4">
    <div class="flex w-[180px] shrink-0 flex-col gap-3">
        <a href="{{ $movie['url'] }}">
            <img
                src="{{ asset($movie['poster']) }}"
                alt="{{ $movie['title'] }} poster"
                loading="lazy"
                class="aspect-[180/238] w-full rounded-2xl object-cover"
            >
        </a>

        <div class="flex flex-col gap-[1.6em] text-[14px] leading-[1.6] text-cb-muted">
            <p>{{ $movie['title'] }}</p>
            <p>{{ $movie['rating'] }} · {{ $movie['duration'] }} min · {{ implode(' · ', $movie['genres']) }}</p>
            <p>In cinemas {{ $movie['release_date']->format('M j, Y') }} →</p>
        </div>
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-3">
        <div class="text-[14px] leading-[1.6] text-cb-muted">
            <p class="text-[22px] leading-[1.2] text-cb-ink">{{ $movie['tagline'] }}</p>
            <p class="mt-[17px]">{{ $movie['description'] }}</p>
        </div>

        <x-cinebook.button :href="$movie['url']" class="self-start">Movie details</x-cinebook.button>
    </div>
</article>
