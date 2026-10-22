{{-- Featured film. Data: $featured --}}
<section class="relative isolate overflow-hidden">
    <img src="{{ asset($featured['image']) }}" alt="" class="absolute inset-0 -z-20 size-full object-cover">
    <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgba(247,244,238,0.96),rgba(247,244,238,0.53)_55%,rgba(247,244,238,0.06))]"></div>

    <div class="mx-auto flex min-h-[590px] max-w-[1440px] items-start px-4 py-8 md:px-8 md:py-12 xl:p-16">
        <div class="flex w-full max-w-[720px] flex-col gap-6 rounded-3xl border border-cb-line bg-white p-6 shadow-[0_12px_16px_rgba(0,0,0,0.04)] md:p-8">
            <div class="flex flex-col gap-4">
                <div class="text-[14px] leading-[1.6] text-cb-muted">
                    <p>NOW SHOWING</p>
                    <p class="mt-[1.6em]">• ONLY AT CINEBOOK DOWNTOWN</p>
                </div>

                <h1 class="text-4xl font-bold leading-[1.1] text-cb-ink md:text-6xl xl:text-[72px]">{{ $featured['title'] }}</h1>

                <div class="text-[16px] leading-[1.6] text-cb-muted">
                    <p>{{ $featured['year'] }} · {{ $featured['rating'] }} · {{ $featured['duration'] }} min · {{ implode(' / ', $featured['genres']) }}</p>
                    <p class="mt-[1.6em]">{{ $featured['synopsis'] }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-cinebook.button :href="$featured['url']" variant="primary">Find seats →</x-cinebook.button>
                <x-cinebook.button :href="$featured['trailer_url']">▷ Watch trailer</x-cinebook.button>
            </div>

            <p class="text-[14px] leading-[1.45] text-cb-muted">{{ $featured['tonight'] }}</p>
        </div>
    </div>
</section>
