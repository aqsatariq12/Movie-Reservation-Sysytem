@php
    $cards = [
        [
            'title' => 'Already used', 'icon' => 'x-circle', 'color' => '#ff6b81',
            'label' => '! Already used',
            'headline' => 'G7 · The Last Horizon · Screen 1',
            'detail' => 'This ticket was admitted today at 18:45. A ticket can only be used once. There is no retry on scan.',
            'meta' => 'Current time 19:04 · Entry window 18:30–20:00',
        ],
        [
            'title' => 'Wrong showtime', 'icon' => 'calendar', 'color' => '#ff6b81',
            'label' => '! Wrong showtime',
            'headline' => 'This ticket is for Night Circuit · 18:00 · Screen 2 IMAX.',
            'detail' => 'You’re scanning The Last Horizon · 19:30 · Screen 1. Choose the matching showtime before scanning again.',
            'meta' => 'Current time 18:54 · Selected show 19:30',
        ],
        [
            'title' => 'Too early', 'icon' => 'clock', 'color' => '#e8b04a',
            'label' => '! Too early',
            'headline' => 'Entry opens today at 18:30.',
            'detail' => 'The Last Horizon · 19:30 · Come back when the entry window opens, 60 minutes before the show.',
            'meta' => 'Current time 18:10 · Opens in 20 minutes',
        ],
        [
            'title' => 'Expired', 'icon' => 'clock-off', 'color' => '#ff6b81',
            'label' => '! Expired',
            'headline' => 'Entry closed today at 20:00.',
            'detail' => 'The Last Horizon · Screen 1 · 19:30. This ticket is more than 30 minutes past the show start and can no longer be admitted.',
            'meta' => 'Current time 20:14 · Closed 14 minutes ago',
        ],
        [
            'title' => 'Void ticket', 'icon' => 'ban', 'color' => '#ff6b81',
            'label' => '! Void · Do not admit',
            'headline' => 'This ticket was cancelled or refunded.',
            'detail' => 'G7 · The Last Horizon · Screen 1. The booking is no longer valid. A refund does not make a void ticket valid again.',
            'meta' => 'Current time 18:54 · Entry window 18:30–20:00',
        ],
        [
            'title' => 'Invalid ticket', 'icon' => 'shield-x', 'color' => '#ff6b81',
            'label' => '! Invalid',
            'headline' => 'We couldn’t recognize this ticket.',
            'detail' => 'No valid token matches this scan. Try scanning once more, type the token carefully or use the booking reference lookup.',
            'meta' => 'Current time 18:54 · Entry window 18:30–20:00',
        ],
    ];
@endphp

<x-layouts::staff active="scan" title="Scan results · CineBook Staff">
    <div class="mx-auto flex max-w-[1440px] flex-col gap-6">
        <div>
            <p class="text-xs uppercase">Cinebook · Companion states</p>
            <h1 class="mt-4 text-3xl font-bold text-[#f4f7ff] sm:text-[38px] sm:leading-[1.2]">No ambiguity at the entrance.</h1>
            <p class="mt-2 text-sm">Each rule has its own icon, text and next step. The focus stays ready for the next scan.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($cards as $c)
                <section class="flex flex-col gap-4 rounded-[14px] border border-[#283750] bg-[#0c1424] p-6">
                    <h2 class="text-2xl font-bold text-[#f4f7ff]">{{ $c['title'] }}</h2>

                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="{{ $c['color'] }}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        @switch($c['icon'])
                            @case('x-circle')
                                <circle cx="12" cy="12" r="10"/><path d="M9 9l6 6M15 9l-6 6"/> @break
                            @case('calendar')
                                <rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4M10 13l4 4M14 13l-4 4"/> @break
                            @case('clock')
                                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/> @break
                            @case('clock-off')
                                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2M4 4l16 16"/> @break
                            @case('ban')
                                <circle cx="12" cy="12" r="10"/><path d="M5 5l14 14"/> @break
                            @case('shield-x')
                                <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9.5 9.5l5 5M14.5 9.5l-5 5"/> @break
                        @endswitch
                    </svg>

                    <p class="text-[10px] font-semibold uppercase tracking-wide">{{ $c['label'] }}</p>
                    <p class="text-lg font-bold leading-[1.4] text-[#f4f7ff]">{{ $c['headline'] }}</p>
                    <p class="text-xs leading-[1.6]">{{ $c['detail'] }}</p>
                    <p class="text-[11px]">{{ $c['meta'] }}</p>

                    <label class="rounded-lg border-2 border-[#347bff] bg-[#101a2d] px-4 py-3">
                        <span class="block text-[10px] uppercase">Next ticket token · focused</span>
                        <input type="text" autocomplete="off" placeholder="Scan or type a token…"
                               class="mt-1 w-full bg-transparent text-xs text-[#f4f7ff] outline-none placeholder:text-[#74849f]">
                    </label>

                    <p class="text-[11px] text-[#87beff]">Submit with Enter · <a href="{{ Route::has('ui.staff.booking-lookup') ? route('ui.staff.booking-lookup') : '#' }}" class="underline">Reference lookup</a> available</p>
                </section>
            @endforeach
        </div>
    </div>
</x-layouts::staff>