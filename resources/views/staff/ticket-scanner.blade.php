@extends('layouts.staff')

@section('content')
<div class="space-y-7 text-[#f4f7ff]">

    {{-- Page heading --}}
    <div>
        <p class="text-sm text-[#a0aec5]">ENTRY DESK · {{ now()->format('D, M d, Y') }}</p>
        <h1 class="mt-2 text-3xl font-bold">Ready when they are.</h1>
        <p class="mt-2 text-sm text-[#a0aec5]">
            Verify customer tickets and prevent duplicate entry.
        </p>
    </div>

    {{-- Scan result --}}
    @if ($errors->any())
        <div class="rounded-xl border border-red-500/40 bg-red-500/10 p-4 text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('scan_result'))
        @php($scan = session('scan_result'))
        <div class="rounded-xl border p-5
            {{ ($scan['type'] ?? '') === 'success'
                ? 'border-green-500/40 bg-green-500/10 text-green-200'
                : 'border-red-500/40 bg-red-500/10 text-red-200' }}">
            <h2 class="text-lg font-semibold">{{ $scan['message'] ?? 'Scan complete' }}</h2>

            @if (!empty($scan['movie']))
                <p class="mt-2 text-sm">Movie: {{ $scan['movie'] }}</p>
            @endif

            @if (!empty($scan['seat']))
                <p class="mt-1 text-sm">Seat: {{ $scan['seat'] }}</p>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">

        {{-- Scanner panel --}}
        <div class="space-y-5">
            <section class="space-y-5 rounded-2xl border border-[#283750] bg-[#101a2d] p-6">

                <div>
                    <label for="showtime_id" class="mb-2 block text-sm text-[#a0aec5]">
                        SHOWTIME
                    </label>

                    <select id="showtime_id"
        name="showtime_id"
        form="ticket-scan-form"
        onchange="window.location.href='{{ route('ui.staff.scanner') }}?showtime_id=' + this.value"
        class="w-full rounded-lg border border-[#283750] bg-[#0c1424] px-4 py-3 text-white focus:border-[#347bff] focus:outline-none">
                        <option value="">Select a showtime</option>
                        @foreach (($showtimes ?? collect()) as $showtime)
                            <option value="{{ $showtime->id }}"
                                @selected((string) old('showtime_id', $selectedShowtimeId ?? '') === (string) $showtime->id)>
                                {{ $showtime->movie->title ?? 'Movie' }}
                                · {{ optional($showtime->starts_at)->format('H:i') }}
                                · {{ $showtime->screen->name ?? 'Screen' }}
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-2 text-xs text-[#74849f]">
                        Choose the showtime before scanning a ticket.
                    </p>
                </div>

                <div class="rounded-2xl border-2 border-[#347bff] bg-[#172f58] p-6 sm:p-8">
                    <h2 class="mb-5 text-2xl font-bold">Scan a ticket</h2>

                    <form id="ticket-scan-form"
                          method="POST"
                          action="{{ route('ui.staff.scanner.scan') }}"
                          class="space-y-4">
                        @csrf

                        <label for="token" class="block text-sm text-[#a0aec5]">
                            TICKET TOKEN
                        </label>

                        <input id="token"
                               name="token"
                               type="text"
                               value="{{ old('token') }}"
                               required
                               autofocus
                               autocomplete="off"
                               placeholder="Scan or type ticket token…"
                               class="w-full rounded-lg border-2 border-[#347bff] bg-[#0c1424] px-4 py-4 text-white placeholder:text-[#74849f] focus:outline-none">

                        <button type="submit"
                                class="w-full rounded-lg bg-[#347bff] px-5 py-3 font-semibold text-white hover:bg-blue-600 sm:w-auto">
                            Submit token · Enter ↵
                        </button>
                    </form>

                    <p class="mt-4 text-sm leading-6 text-[#a0aec5]">
                        USB scanner: scan then press Enter. You can also type the ticket token manually.
                    </p>
                </div>

                <p class="text-xs text-[#74849f]">
                    No camera needed · Single-use admission · No undo or reset
                </p>
            </section>

            {{-- Recent attempts --}}
            <section class="overflow-hidden rounded-2xl border border-[#283750] bg-[#101a2d]">
                <div class="border-b border-[#283750] p-5">
                    <h2 class="text-xl font-bold">Recent 10 attempts</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[#0c1424] text-xs text-[#a0aec5]">
                            <tr>
                                <th class="px-4 py-3">TIME</th>
                                <th class="px-4 py-3">RESULT</th>
                                <th class="px-4 py-3">SEAT</th>
                                <th class="px-4 py-3">TICKET</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#283750]">
                            @forelse (($recentScans ?? collect()) as $scan)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-[#a0aec5]">
                                        {{ optional($scan->scanned_at)->format('H:i:s') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-4">
                                        {{ strtoupper(str_replace('_', ' ', $scan->result->value ?? $scan->result ?? 'unknown')) }}
                                    </td>
                                    <td class="px-4 py-4">
                                      {{ $scan->ticket?->bookingSeat?->seat
    ? (($scan->ticket->bookingSeat->seat->row_label ?? '')
        . ($scan->ticket->bookingSeat->seat->seat_number ?? ''))
    : '—' }}
                                    </td>
                                    <td class="px-4 py-4 text-[#a0aec5]">
                                        {{ $scan->ticket_id ? 'T-' . str_pad((string) $scan->ticket_id, 4, '0', STR_PAD_LEFT) : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-[#74849f]">
                                        No scan attempts yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- Showtime summary --}}
        <aside class="space-y-5">
            <section class="rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
                <p class="text-xs text-[#a0aec5]">SELECTED SHOWTIME</p>

                @if (!empty($selectedShowtime))
                    <h2 class="mt-4 text-2xl font-bold">
                        {{ $selectedShowtime->movie->title ?? 'Movie' }}
                    </h2>

                    <p class="mt-2 text-sm text-[#a0aec5]">
                        {{ $selectedShowtime->screen->name ?? 'Screen' }}
                    </p>

                    <div class="mt-6">
                        <p class="text-4xl font-bold">
                            {{ $admittedCount ?? 0 }} / {{ $ticketsSold ?? 0 }}
                        </p>
                        <p class="mt-2 text-sm text-[#a0aec5]">
                            Guests admitted / tickets sold
                        </p>
                    </div>

                    <div class="mt-5 space-y-2 text-sm text-[#a0aec5]">
                        <p>Starts: {{ optional($selectedShowtime->starts_at)->format('D, M d · H:i') ?? '—' }}</p>
                        <p>Entry opens: {{ optional($selectedShowtime->starts_at)->subHour()->format('H:i') ?? '—' }}</p>
                        <p>Entry closes: {{ optional($selectedShowtime->starts_at)->addMinutes(30)->format('H:i') ?? '—' }}</p>
                    </div>
                @else
                    <p class="mt-4 text-sm leading-6 text-[#a0aec5]">
                        Select a showtime to see ticket and admission totals.
                    </p>
                @endif
            </section>

            <section class="rounded-xl border border-[#283750] bg-[#172f58] p-5">
                <h3 class="font-semibold">Damaged QR?</h3>
                <p class="mt-2 text-sm leading-6 text-[#a0aec5]">
                    Use the booking reference lookup to find a guest’s ticket and seat status.
                    Payment information is not needed.
                </p>
            </section>
        </aside>
    </div>
</div>

{{-- Keep scanner ready for the next USB/manual token --}}


<script>

document.addEventListener('DOMContentLoaded', function () {
    const tokenInput = document.getElementById('token');
    const scanForm = document.getElementById('ticket-scan-form');
    const submitButton = scanForm?.querySelector('button[type="submit"]');

    if (!tokenInput || !scanForm || !submitButton) return;

    let isSubmitting = false;

    tokenInput.focus();

    scanForm.addEventListener('submit', function (event) {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        if (tokenInput.value.trim() === '') {
            event.preventDefault();
            return;
        }

        isSubmitting = true;
        submitButton.disabled = true;
        submitButton.textContent = 'Scanning...';
    });

    tokenInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();

            if (!isSubmitting && tokenInput.value.trim() !== '') {
                scanForm.requestSubmit();
            }
        }
    });
});

</script>

@endsection
