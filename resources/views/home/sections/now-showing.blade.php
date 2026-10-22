{{-- Data: $nowShowing --}}
<section class="flex flex-col gap-10">
    <div class="flex items-center gap-4">
        <h2 class="flex-1 text-[32px] font-bold leading-[1.13] text-cb-ink">Now showing</h2>
        <a href="{{ Route::has('movies.index') ? route('movies.index') : '#' }}" class="whitespace-nowrap text-[14px] leading-[1.45] text-cb-brand hover:underline">Explore all movies →</a>
    </div>

    <div class="grid grid-cols-2 gap-6 md:grid-cols-3 xl:grid-cols-5">
        @foreach ($nowShowing as $movie)
            <x-cinebook.movie-card :movie="$movie" />
        @endforeach
    </div>
</section>
