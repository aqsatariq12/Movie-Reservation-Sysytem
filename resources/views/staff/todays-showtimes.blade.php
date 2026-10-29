
@php
    $currentTime = now();

    $nearestShow = $showtimes->first(function ($showtime) use ($currentTime) {
        return $showtime->starts_at
            && $currentTime->between(
                $showtime->starts_at->copy()->subMinutes(60),
                $showtime->starts_at->copy()->addMinutes(30)
            );
    });

    if (!$nearestShow) {
        $nearestShow = $showtimes->first(
            fn ($showtime) => $showtime->starts_at
                && $showtime->starts_at->greaterThanOrEqualTo($currentTime)
        );
    }

    $nearestEntryOpen = $nearestShow
        && $currentTime->between(
            $nearestShow->starts_at->copy()->subMinutes(60),
            $nearestShow->starts_at->copy()->addMinutes(30)
        );

    $rows = $showtimes->map(function ($showtime) use ($currentTime) {
        $startsAt = $showtime->starts_at;
        $entryStart = $startsAt?->copy()->subMinutes(60);
        $entryEnd = $startsAt?->copy()->addMinutes(30);

        if (!$startsAt) {
            $entryStatus = 'Time unavailable';
        } elseif ($currentTime->lt($entryStart)) {
            $entryStatus = 'Not open';
        } elseif ($currentTime->gt($entryEnd)) {
            $entryStatus = 'Closed';
        } else {
            $entryStatus = 'Open';
        }

        return [
            'movie' => $showtime->movie?->title ?? 'Movie unavailable',
            'screen' => $showtime->screen?->name ?? 'Screen unavailable',
            'time' => $startsAt?->format('H:i') ?? '—',
            'expected' => $showtime->tickets_expected,
            'scanned' => $showtime->tickets_scanned,
            'entry_window' => $entryStart && $entryEnd
                ? $entryStart->format('H:i') . '–' . $entryEnd->format('H:i')
                    . ' · ' . $entryStatus
                : 'Unavailable',
        ];
    });
@endphp

<x-layouts::staff active="today" title="Today · CineBook Staff">
    <div class="mx-auto flex max-w-[1440px] flex-col gap-6">
        <div>
            <p class="text-xs uppercase">
                Entry desk · {{ $today->format('D, M d, Y') }}
            </p>
            <h1 class="mt-4 text-3xl font-bold text-[#f4f7ff] sm:text-[38px] sm:leading-[1.2]">
                Today at Downtown
            </h1>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ($stats as [$label, $value])
                <div class="rounded-[14px] border border-[#283750] bg-[#101a2d] p-6">
                    <p class="text-xs">{{ $label }}</p>
                    <p class="mt-3 text-4xl font-bold text-[#f4f7ff]">
                        {{ $value }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="flex flex-col gap-4 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-3">
                <p class="text-xs font-semibold uppercase {{ $nearestEntryOpen ? 'text-[#60d4ae]' : 'text-[#a0aec5]' }}">
                    {{ $nearestShow ? ($nearestEntryOpen ? '✓ Entry open · Nearest show' : 'Nearest show') : 'No shows scheduled today' }}
                </p>

                <h2 class="text-3xl font-bold text-[#f4f7ff]">
                    {{ $nearestShow?->movie?->title ?? 'No upcoming show' }}
                </h2>

                <p class="text-sm">
                    @if ($nearestShow)
                        {{ $nearestShow->screen?->name ?? 'Screen unavailable' }}
                        · {{ $nearestShow->starts_at?->format('H:i') ?? 'Time unavailable' }}
                    @else
                        There are no showtimes available for today.
                    @endif
                </p>

                @if ($nearestShow)
                    <p class="text-sm">
                        Entry
                        {{ $nearestShow->starts_at?->copy()->subMinutes(60)->format('H:i') ?? '—' }}–{{ $nearestShow->starts_at?->copy()->addMinutes(30)->format('H:i') ?? '—' }}
                        · {{ $nearestShow->tickets_scanned }} / {{ $nearestShow->tickets_expected }} scanned
                    </p>
                @endif
            </div>

            <div class="flex flex-col gap-4 sm:items-end">
                <p class="text-xs">Local time · {{ $currentTime->format('H:i') }}</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('ui.staff.scanner') }}"
                       class="rounded-lg bg-[#347bff] px-5 py-[13px] text-sm font-semibold text-white hover:brightness-110">
                        Scan this show →
                    </a>

                    <a href="{{ Route::has('ui.staff.roster') ? route('ui.staff.roster') : '#' }}"
                       class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm font-semibold text-[#f4f7ff] hover:brightness-125">
                        View roster
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-[14px] border border-[#283750] bg-[#101a2d] p-4">
            <table class="w-full min-w-[760px] text-left text-xs">
                <thead>
                    <tr class="h-14 uppercase text-[#f4f7ff]">
                        @foreach (['Movie', 'Screen', 'Showtime', 'Sold / scanned', 'Entry window', 'Action'] as $h)
                            <th class="px-3 font-normal {{ $loop->first ? '' : 'text-[#a0aec5]' }}">
                                {{ $h }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @forelse ($rows as $row)
                        <tr class="h-[54px]">
                            <td class="px-3 text-[#f4f7ff]">{{ $row['movie'] }}</td>
                            <td class="px-3">{{ $row['screen'] }}</td>
                            <td class="px-3">{{ $row['time'] }}</td>
                            <td class="px-3">{{ $row['expected'] }} / {{ $row['scanned'] }}</td>
                            <td class="px-3">{{ $row['entry_window'] }}</td>
                            <td class="px-3">
                                <a href="{{ Route::has('ui.staff.roster') ? route('ui.staff.roster') : '#' }}"
                                   class="text-[#87beff]">
                                    Roster →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-[#a0aec5]">
                                No showtimes scheduled for today.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
            <p>ⓘ Admission desk</p>
            <p>
                Use a USB keyboard scanner or enter the ticket token manually.
                For damaged QR codes, look up the booking reference. No camera is required.
            </p>
        </div>
    </div>
</x-layouts::staff>
