{{-- "Find your mood" genre shortcuts. Data: $moods --}}
<section class="flex flex-col gap-4 rounded-3xl border border-cb-line bg-white p-6 shadow-[0_8px_12px_rgba(0,0,0,0.03)] lg:flex-row lg:items-center">
    <div class="flex-1 text-[14px] leading-[1.6] text-cb-muted">
        <p>FIND YOUR MOOD</p>
        <h2 class="mt-[17px] text-[26px] font-bold leading-[1.2] text-cb-ink">A story for every kind of night</h2>
    </div>

    <div class="flex flex-wrap gap-4">
        @foreach ($moods as $mood)
            <x-cinebook.button :href="$mood['url']">{{ $mood['name'] }} →</x-cinebook.button>
        @endforeach
    </div>
</section>
