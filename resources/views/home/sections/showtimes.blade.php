{{-- "What's on at Downtown?" card. Data: $dates, $showtimeSection --}}
<section id="showtimes"
    class="flex flex-col gap-5 rounded-3xl border border-cb-line bg-white p-6 shadow-[0_8px_12px_rgba(0,0,0,0.03)]">
    <div class="grid gap-4 md:min-h-[66px] md:grid-cols-2">
        <p class="self-start text-[14px] leading-[1.6] text-cb-muted">MAKE IT A MOVIE NIGHT</p>
        <h2 class="self-center text-[26px] font-bold leading-[1.2] text-cb-ink">What’s on at Downtown?</h2>
    </div>

    <p class="text-[13px] leading-[1.45] text-cb-muted">{{ $showtimeSection['subtitle'] }}</p>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
        @foreach ($dates as $date)
            <a href="{{ $date['url'] }}" @class([
                'rounded-xl p-4 text-center text-[13px] leading-[1.45]',
                'bg-cb-brand text-white' => $date['active'],
                'border border-cb-line bg-white text-cb-ink hover:border-cb-brand' => !$date['active'],
            ]) @if ($date['active']) aria-current="date" @endif>{{ $date['label'] }}</a>
        @endforeach
    </div>

    @foreach ($showtimeSection['rows'] as $row)
        <div class="flex flex-wrap items-center gap-x-4 gap-y-3 lg:flex-nowrap">
            <p class="w-full text-[14px] leading-[1.45] text-cb-muted lg:w-auto lg:flex-1">
                {{ $row['title'] }} · {{ $row['screen'] }} · Standard {{ $row['standard_price'] }} / Premium
                {{ $row['premium_price'] }}
            </p>

            @foreach ($row['times'] as $slot)
                <span @class(['text-[12px] lg:flex-1', 'text-cb-faint' => $slot['closed'], 'text-cb-brand' => !$slot['closed']])>
                    • {{ $slot['time'] }}@if ($slot['closed']) · Closed @endif
                </span>
            @endforeach

            <x-cinebook.button :href="$row['movie_url']">
                All showtimes →
            </x-cinebook.button>
        </div>
    @endforeach
</section>