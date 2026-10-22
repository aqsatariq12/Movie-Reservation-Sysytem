{{-- Data: $comingSoon --}}
<section class="flex flex-col gap-10">
    <div class="flex items-center gap-4">
        <h2 class="flex-1 text-[32px] font-bold leading-[1.13] text-cb-ink">Coming soon</h2>
        <p class="whitespace-nowrap text-[14px] leading-[1.45] text-cb-muted">New stories on the horizon</p>
    </div>

    <div class="grid gap-7 lg:grid-cols-3">
        @foreach ($comingSoon as $movie)
            <x-cinebook.coming-soon-card :movie="$movie" />
        @endforeach
    </div>
</section>
