
@php
    $resultLabels = [
        'admitted' => 'ADMITTED',
        'already_used' => 'ALREADY USED',
        'wrong_showtime' => 'WRONG SHOWTIME',
        'too_early' => 'TOO EARLY',
        'expired' => 'ENTRY EXPIRED',
        'void' => 'VOID TICKET',
        'invalid' => 'INVALID TICKET',
    ];

    $resultMessages = [
        'admitted' => 'Single-use token accepted. Entry allowed.',
        'already_used' => 'This ticket has already been scanned.',
        'wrong_showtime' => 'This ticket belongs to a different showtime.',
        'too_early' => 'Entry is not open yet.',
        'expired' => 'The entry window has closed.',
        'void' => 'This ticket is void.',
        'invalid' => 'The ticket or booking could not be validated.',
    ];

    $resultKey = is_string($scanResultValue ?? null)
        ? $scanResultValue
        : '';

    $isAdmitted = $resultKey === 'admitted';
    $resultTitle = $resultLabels[$resultKey] ?? 'NO SCAN RESULT';
    $resultMessage = $resultMessages[$resultKey]
        ?? 'Scan a ticket to see its verification result.';

    $movieTitle = $showtime?->movie?->title ?? 'No showtime available';
    $screenName = $showtime?->screen?->name ?? 'Screen unavailable';

    $startsAt = $showtime?->starts_at;
    $entryOpens = $startsAt?->copy()->subHour();
    $entryCloses = $startsAt?->copy()->addMinutes(30);

    $seatType = $latestScan?->ticket?->bookingSeat?->seat?->type;
@endphp

<x-layouts::staff active="scan" title="Scan Result · CineBook Staff">
    <div class="mx-auto flex max-w-[1440px] flex-col gap-6">

        {{-- Page heading --}}
        <div>
            <p class="text-xs uppercase">
                Entry desk · {{ now()->format('D, M d, Y') }}
            </p>
            <h1 class="mt-4 text-3xl font-bold text-[#f4f7ff] sm:text-[38px] sm:leading-[1.2]">
                Welcome to the show.
            </h1>
            <p class="mt-2 text-sm text-[#a0aec5]">
                Review the latest ticket verification result.
            </p>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">

            <div class="flex flex-col gap-5">

                {{-- Latest scan result --}}
                <section class="flex flex-col gap-5 rounded-[14px] border p-6 sm:p-8
                    {{ $isAdmitted
                        ? 'border-[#2f8f6a] bg-[#123a30]'
                        : 'border-[#8d4554] bg-[#351d2a]' }}">

                    <div class="flex items-center gap-4">
                        @if ($isAdmitted)
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                                <circle cx="20" cy="20" r="18" stroke="#60d4ae" stroke-width="2.5"/>
                                <path d="M12 21l6 6 10-12" stroke="#60d4ae" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @else
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                                <circle cx="20" cy="20" r="18" stroke="#ff8c9a" stroke-width="2.5"/>
                                <path d="M14 14l12 12M26 14L14 26" stroke="#ff8c9a" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        @endif

                        <p class="text-3xl font-bold tracking-wide text-white sm:text-[40px]">
                            {{ $resultTitle }}
                        </p>
                    </div>

                    <p class="text-xl font-bold text-white">
                        {{ $movieTitle }} · {{ $screenName }}
                    </p>

                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <p class="text-5xl font-bold text-white sm:text-6xl">
                            {{ $seatLabel ?: '—' }}
                        </p>

                        <div class="text-xs leading-[1.8] text-[#cfe9df] sm:text-right">
                            <p>{{ $seatType ? ucfirst(str_replace('_', ' ', $seatType->value)) . ' seat' : 'Seat information' }}</p>
                            <p>Ticket {{ $ticketLabel ?: '—' }}</p>
                            <p>
                                Scanned at
                                {{ $latestScan?->scanned_at?->format('H:i:s') ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs leading-5 {{ $isAdmitted ? 'text-[#cfe9df]' : 'text-[#ffd2d8]' }}">
                        {{ $resultMessage }}
                    </p>
                </section>

                {{-- Scan another ticket --}}
                <form id="admitted-scan-form"
      method="POST"
      action="{{ route('ui.staff.scanner.scan') }}"
      class="flex flex-col gap-2">
                    @csrf

                    @if ($showtime)
                        <input type="hidden" name="showtime_id" value="{{ $showtime->id }}">
                    @endif

                    <label class="rounded-lg border-2 border-[#347bff] bg-[#101a2d] px-4 py-3">
                        <span class="block text-[10px] uppercase text-[#a0aec5]">
                            Next ticket token · focused
                        </span>
                        <input type="text"
                               name="token"
                               autofocus
                               autocomplete="off"
                               required
                               placeholder="Scan next ticket or type token…"
                               class="mt-1 w-full bg-transparent text-sm text-[#f4f7ff] outline-none placeholder:text-[#74849f]">
                    </label>

                    @error('token')
                        <p class="text-sm text-red-300">{{ $message }}</p>
                    @enderror

                    @if ($showtime)
                        <p class="text-xs text-[#a0aec5]">
                            USB keyboard scanner · Press Enter to submit
                        </p>
                    @else
                        <p class="text-xs text-amber-300">
                            A showtime is required before scanning another ticket.
                            <a href="{{ route('ui.staff.scanner') }}" class="underline">Open scanner</a>
                        </p>
                    @endif

                    <button  id="admitted-scan-button" type="submit"
                            @disabled(! $showtime)
                            class="mt-2 w-full rounded-lg bg-[#347bff] px-5 py-3 text-sm font-semibold text-white hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50 sm:w-fit">
                        Scan next ticket
                    </button>
                </form>

                {{-- Recent scan attempts --}}
                <section class="flex flex-col gap-3">
                    <h2 class="text-lg font-bold text-[#f4f7ff]">
                        Recent 10 attempts
                    </h2>

                    <div class="overflow-x-auto rounded-[14px] border border-[#283750] bg-[#101a2d] p-4">
                        <table class="w-full min-w-[480px] text-left text-xs">
                            <thead>
                                <tr class="h-10 uppercase text-[#a0aec5]">
                                    @foreach (['Time', 'Result', 'Seat', 'Ticket'] as $heading)
                                        <th class="px-3 font-normal">{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($recentScans as $attempt)
                                    @php
                                        $attemptResult = $attempt->result instanceof \App\Enums\ScanResult
                                            ? $attempt->result->value
                                            : (string) $attempt->result;

                                        $attemptSeat = $attempt->ticket?->bookingSeat?->seat;

                                        $attemptSeatLabel = $attemptSeat
                                            ? ($attemptSeat->row_label ?? '') . ($attemptSeat->seat_number ?? '')
                                            : '—';

                                        $attemptTicketLabel = $attempt->ticket_id
                                            ? 'T-' . str_pad((string) $attempt->ticket_id, 4, '0', STR_PAD_LEFT)
                                            : '—';
                                    @endphp

                                    <tr class="h-10 border-t border-[#283750]/60">
                                        <td class="whitespace-nowrap px-3 text-[#a0aec5]">
                                            {{ $attempt->scanned_at?->format('H:i:s') ?? '—' }}
                                        </td>
                                        <td class="px-3 {{ $attemptResult === 'admitted' ? 'text-[#60d4ae]' : 'text-[#ff8c9a]' }}">
                                            {{ strtoupper(str_replace('_', ' ', $attemptResult ?: 'unknown')) }}
                                        </td>
                                        <td class="px-3 text-[#f4f7ff]">
                                            {{ $attemptSeatLabel }}
                                        </td>
                                        <td class="px-3 text-[#a0aec5]">
                                            {{ $attemptTicketLabel }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="border-t border-[#283750]/60">
                                        <td colspan="4" class="px-3 py-8 text-center text-[#74849f]">
                                            No scan attempts recorded for your account yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {{-- Showtime summary --}}
            <aside class="flex flex-col gap-4 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6">

                <div class="rounded-lg border border-[#283750] bg-[#0b1424] p-3 text-xs">
                    <p class="uppercase text-[#a0aec5]">Showtime</p>
                    <p class="mt-1 text-[#f4f7ff]">
                        {{ $showtime?->movie?->title ?? 'No showtime selected' }}
                        @if ($startsAt)
                            · {{ $startsAt->format('H:i') }}
                        @endif
                    </p>
                </div>

                @if ($startsAt)
                    @php
                        $currentTime = now();

                        $entryStatus = $currentTime->lt($entryOpens)
                            ? 'Entry not open'
                            : ($currentTime->gt($entryCloses) ? 'Entry closed' : 'Entry open');

                        $entryStatusColor = $entryStatus === 'Entry open'
                            ? 'text-[#60d4ae]'
                            : 'text-[#ffb86b]';
                    @endphp

                    <p class="text-xs {{ $entryStatusColor }}">
                        · {{ $entryStatus }}
                    </p>
                @else
                    <p class="text-xs text-[#a0aec5]">· Showtime unavailable</p>
                @endif

                <p class="text-5xl font-bold text-[#f4f7ff]">
                    {{ $admittedCount }} / {{ $ticketsSold }}
                </p>
                <p class="text-xs text-[#a0aec5]">Scanned / sold tickets</p>

                <div class="space-y-2 text-xs leading-[1.8] text-[#a0aec5]">
                    <p>{{ $screenName }}</p>
                    <p>Entry opens: {{ $entryOpens?->format('H:i') ?? '—' }}</p>
                    <p>Entry closes: {{ $entryCloses?->format('H:i') ?? '—' }}</p>
                    <p>Current time: {{ now()->format('H:i') }}</p>
                </div>

                <div>
                    <a href="{{ route('ui.staff.roster', $showtime ? ['showtime_id' => $showtime->id] : []) }}"
                       class="inline-block rounded-lg border border-[#283750] bg-[#17243b] px-4 py-2 text-xs font-semibold text-[#f4f7ff] hover:brightness-125">
                        Show roster
                    </a>
                </div>
            </aside>
        </div>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('admitted-scan-form');
    const button = document.getElementById('admitted-scan-button');

    if (!form || !button) return;

    let isSubmitting = false;

    form.addEventListener('submit', function (event) {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        const tokenInput = form.querySelector('input[name="token"]');

        if (!tokenInput || tokenInput.value.trim() === '') {
            event.preventDefault();
            return;
        }

        isSubmitting = true;
        button.disabled = true;
        button.textContent = 'Scanning...';
    });
});
</script>
</x-layouts::staff>
