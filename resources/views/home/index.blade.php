<x-layouts.cinebook title="CineBook Downtown — Now showing">
    @include('home.sections.hero')

    <div class="mx-auto flex max-w-[1440px] flex-col gap-10 px-4 py-10 md:px-8 xl:px-16">
        @include('home.sections.showtimes')
        @include('home.sections.now-showing')
        @include('home.sections.coming-soon')
        @include('home.sections.moods')
    </div>
</x-layouts.cinebook>
