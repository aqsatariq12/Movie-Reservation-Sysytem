@props(['booking', 'seatSummary', 'typeLabel', 'total'])
@php($movie = $booking->showtime->movie)
@php($showtime = $booking->showtime)

<section {{ $attributes->merge(['class' => 'flex gap-5 rounded-[24px] border border-[#e3d8c8] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.03)]']) }}>
    <div class="h-[180px] w-[104px] shrink-0 overflow-hidden rounded-[10px] bg-gradient-to-br from-[#1f2937] to-[#347bff]">
        @if ($movie->poster_path)
            <img src="{{ asset('/' . $movie->poster_path) }}" alt="{{ $movie->title }} poster" class="h-full w-full object-cover">
        @endif
    </div>

    <div class="min-w-0 flex-1 text-sm leading-[1.8] text-[#6b7280]">
        <h2 class="text-2xl font-bold leading-[1.8] text-[#1f2937]">{{ $movie->title }}</h2>
        <p>{{ $showtime->starts_at->format('D, F j, Y · H:i') }}</p>
        <p>CineBook Downtown · {{ $showtime->screen->name }}</p>
        <p>{{ $movie->duration_minutes }} min @if ($movie->rating) · {{ $movie->rating }} @endif</p>
        <p>{{ $seatSummary }} · {{ $typeLabel }}</p>
        <p>{{ $booking->booking_reference }}</p>
        <p>Total · {{ $total }} {{ $booking->currency }}</p>
    </div>
</section>