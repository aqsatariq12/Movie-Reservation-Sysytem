<x-layouts::staff active="roster" title="Show roster · CineBook Staff">
    <div class="mx-auto flex max-w-[1440px] flex-col gap-6">
        <div>
            <p class="text-xs uppercase">
                Entry desk · {{ now()->format('D, M d, Y') }}
            </p>
            <h1 class="mt-4 text-3xl font-bold text-[#f4f7ff] sm:text-[38px] sm:leading-[1.2]">
                Show roster
            </h1>
        </div>

        <form method="GET" action="{{ route('ui.staff.roster') }}"
              class="grid gap-4 sm:grid-cols-2">
            <label class="rounded-lg border border-[#283750] bg-[#101a2d] px-4 py-3">
                <span class="block text-[10px] uppercase">Showtime</span>
                <select name="showtime_id" onchange="this.form.submit()"
                        class="mt-1 w-full bg-transparent text-sm text-[#f4f7ff] outline-none">
                    @forelse ($showtimes as $showtime)
                        <option value="{{ $showtime->id }}"
                                class="bg-[#101a2d]"
                                @selected((int) $selectedShowtimeId === (int) $showtime->id)>
                            {{ $showtime->movie_title }}
                            · {{ \Illuminate\Support\Carbon::parse($showtime->starts_at)->format('M d, H:i') }}
                            · {{ $showtime->screen_name }}
                        </option>
                    @empty
                        <option class="bg-[#101a2d]">No showtimes available</option>
                    @endforelse
                </select>
            </label>

            <label class="rounded-lg border border-[#283750] bg-[#101a2d] px-4 py-3">
                <span class="block text-[10px] uppercase">Find by reference</span>
                <div class="mt-1 flex gap-2">
                    <input type="search" name="search" value="{{ $search }}"
                           placeholder="Search order…" autocomplete="off"
                           class="w-full bg-transparent text-sm text-[#f4f7ff] outline-none placeholder:text-[#74849f]">
                    <button type="submit"
                            class="text-sm font-semibold text-[#60d4ae]">
                        Search
                    </button>
                </div>
            </label>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-xs text-[#60d4ae]">
                ✓ {{ $admittedTotal }} / {{ $seatTotal }} admitted
            </p>
            <p class="text-xs">
                {{ $confirmedTotal }} confirmed bookings · Read-only admission roster
            </p>
            <a href="{{ Route::has('ui.staff.admitted-result') ? route('ui.staff.admitted-result') : '#' }}"
               class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm font-semibold text-[#f4f7ff] hover:brightness-125">
                Back to scanner →
            </a>
        </div>

        <div class="overflow-x-auto rounded-[14px] border border-[#283750] bg-[#101a2d] p-4">
            <table class="w-full min-w-[720px] text-left text-xs">
                <thead>
                    <tr class="h-14 uppercase">
                        @foreach (['Booking reference', 'Customer name', 'Seats', 'Seat count', 'Admitted / count'] as $heading)
                            <th class="px-3 font-normal {{ $loop->first ? 'text-[#f4f7ff]' : '' }}">
                                {{ $heading }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr class="h-[54px] border-t border-[#283750]/60">
                            <td class="px-3 text-[#f4f7ff]">
                                {{ $booking->booking_reference }}
                            </td>
                            <td class="px-3">{{ $booking->customer_name }}</td>
                            <td class="px-3">
                                {{ $booking->seat_labels !== '' ? $booking->seat_labels : '—' }}
                            </td>
                            <td class="px-3">{{ $booking->seat_count }}</td>
                            <td class="px-3">
                                {{ $booking->admitted_count }} / {{ $booking->seat_count }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-10 text-center text-sm text-[#74849f]">
                                @if (!$selectedShowtimeId)
                                    No showtimes are available.
                                @elseif ($search !== '')
                                    No confirmed bookings match this reference.
                                @else
                                    No confirmed bookings for this showtime yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-xs">
                Showing {{ $bookings->count() }} of {{ $confirmedTotal }} confirmed bookings
            </p>
        </div>

        <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
            <p>ⓘ Admission information only</p>
            <p>
                Names, references, seats and scan counts. This view does not show
                email addresses or payment information.
            </p>
        </div>
    </div>
</x-layouts::staff>
