@php
    $money = fn(int $c) => \App\Models\Showtime::money($c);
    $closesAt = $showtime->starts_at->copy()->subMinutes($cutoff);
    $isVerified = auth()->check() && auth()->user()->hasVerifiedEmail();
@endphp

<x-layouts.cinebook :title="$movie->title . ' · Select seats'">
    <section class="mx-auto w-full max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 lg:py-10">

        {{-- Page heading --}}
        <div class="mb-7 flex flex-col gap-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cb-ink/60">
                <span class="md:hidden">01 Seats → 02 Checkout → 03 Tickets</span>
                <span class="hidden md:inline">01 Select seats → 02 Checkout → 03 Your tickets</span>
            </p>

            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-3xl font-bold leading-tight tracking-tight text-cb-ink md:text-4xl">
                        <span class="md:hidden">Choose your seats</span>
                        <span class="hidden md:inline">A great night starts with a great seat</span>
                    </h1>
                    <p class="mt-2 text-sm text-cb-ink/65">
                        Choose your seats below. You can select up to {{ $max }} seats.
                    </p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-cb-ink/70 shadow-sm">
                    {{ $screen->name }}
                </span>
            </div>

            {{-- Date navigation --}}
            <div class="flex flex-wrap gap-x-6 gap-y-3 border-b border-slate-200">
                @foreach ($days as $date => $dayShows)
                    @php $first = $dayShows->first(); @endphp
                    <a
                        href="{{ route('showtimes.seats', $first) }}"
                        class="border-b-2 pb-3 text-sm font-semibold transition
                            {{ $first->starts_at->isSameDay($showtime->starts_at)
                                ? 'border-blue-600 text-blue-700'
                                : 'border-transparent text-cb-ink/60 hover:border-slate-300 hover:text-cb-ink' }}"
                    >
                        {{ $first->starts_at->format('D · M j') }}
                    </a>
                @endforeach
            </div>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Main two-column layout --}}
        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[340px_minmax(0,1fr)] xl:gap-8">

            {{-- Left column: movie details and selection --}}
            <aside class="flex min-w-0 flex-col gap-6 xl:sticky xl:top-6">

                {{-- Movie and showtime details --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex gap-4">
                                                @if ($movie->poster_url)
                            <img
                                src="{{ $movie->poster_url }}"
                                alt="{{ $movie->title }} poster"
                                class="h-[150px] w-[100px] shrink-0 rounded-xl object-cover shadow-sm sm:h-[170px] sm:w-[112px]"
                            >
                        @else
                            <div class="flex h-[150px] w-[100px] shrink-0 items-center justify-center rounded-xl bg-blue-50 text-3xl font-bold text-blue-700 sm:h-[170px] sm:w-[112px]">
                                {{ \Illuminate\Support\Str::of($movie->title)->substr(0, 1) }}
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">
                                {{ $screen->name }}
                            </p>
                            <h2 class="mt-2 break-words text-xl font-bold leading-snug text-cb-ink">
                                {{ $movie->title }}
                            </h2>
                            <p class="mt-3 text-sm leading-6 text-cb-ink/65">
                                {{ $movie->rating }} · {{ $movie->duration_minutes }} min
                            </p>
                            <p class="text-sm leading-6 text-cb-ink/65">
                                {{ $genres->implode(' · ') }}
                            </p>
                        </div>
                    </div>

                    <div class="my-5 border-t border-slate-100"></div>

                    <p class="text-sm font-semibold text-cb-ink">
                        {{ $showtime->starts_at->format('l, F j, Y') }}
                    </p>
                    <p class="mt-1 text-sm text-cb-ink/60">
                        Choose a showtime
                    </p>

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        @foreach ($sameDay as $s)
                            @php $isClosed = $s->starts_at->copy()->subMinutes($cutoff)->lte($now); @endphp

                            @if ($isClosed)
                                <span class="flex items-center justify-center rounded-lg border border-slate-200 bg-slate-100 px-2 py-3 text-sm text-slate-400">
                                    {{ $s->starts_at->format('H:i') }}
                                </span>
                            @else
                                <a
                                    href="{{ route('showtimes.seats', $s) }}"
                                    class="flex items-center justify-center rounded-lg border px-2 py-3 text-sm font-semibold transition
                                        {{ $s->id === $showtime->id
                                            ? 'border-blue-600 bg-blue-600 text-white shadow-sm'
                                            : 'border-slate-200 bg-white text-cb-ink hover:border-blue-300 hover:bg-blue-50' }}"
                                >
                                    {{ $s->starts_at->format('H:i') }}
                                </a>
                            @endif
                        @endforeach
                    </div>

                    <p class="mt-4 text-xs leading-5 text-cb-ink/55">
                        Sales close at {{ $closesAt->format('H:i') }}.
                    </p>
                </section>

                {{-- Selection summary --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-xl font-bold text-cb-ink">Your selection</h2>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                            Max {{ $max }}
                        </span>
                    </div>

                    <div id="selection-list" class="mt-4 flex flex-wrap gap-2 text-sm text-blue-700"></div>

                    <p id="selection-total" class="mt-4 text-lg font-bold text-cb-ink">
                        Not held yet.
                    </p>

                    <div class="mt-4 space-y-2 text-sm text-cb-ink/65">
                        <div class="flex items-center justify-between gap-3">
                            <span>Standard</span>
                            <span class="font-semibold text-cb-ink">{{ $money($showtime->base_price_cents) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Premium</span>
                            <span class="font-semibold text-cb-ink">{{ $money($showtime->base_price_cents + $surcharge) }}</span>
                        </div>
                    </div>

                    <p class="mt-4 text-xs leading-5 text-cb-ink/60">
                        Premium seats include a {{ $money($surcharge) }} surcharge. Your hold starts only after you continue.
                    </p>

                    @error('seats')
                        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                            {{ $message }}
                        </div>
                    @enderror

                    @if ($closed)
                        <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            Sales for this showtime have closed.
                        </p>
                    @elseif ($isVerified)
                        <form id="hold-form" class="mt-5" method="POST" action="{{ route('holds.store', $showtime) }}">
                            @csrf
                            <div id="hold-inputs"></div>
                            <button
                                id="hold-button"
                                type="submit"
                                disabled
                                class="flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500 disabled:shadow-none"
                            >
                                Select seats
                            </button>
                        </form>
                    @endif
                </section>

                {{-- Guest / verification notices --}}
                @guest
                    <section class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
                        <p class="font-semibold">Sign in to hold seats</p>
                        <p class="mt-1 text-blue-900/75">
                            Sign in first and we’ll bring you back to this showtime. Your email must be verified.
                        </p>
                        <a
                            href="{{ route('login') }}"
                            class="mt-3 inline-flex rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-700"
                        >
                            Sign in
                        </a>
                    </section>
                @else
                    @unless ($isVerified)
                        <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                            Please verify your email address before holding seats.
                        </section>
                    @endunless
                @endguest

                <p class="px-1 text-xs leading-5 text-cb-ink/60">
                    Your {{ $holdMinutes }}-minute hold starts only after you choose Hold seats. All selected seats are held together or none are.
                </p>
            </aside>

            {{-- Right column: seat map --}}
            <div class="flex min-w-0 flex-col gap-5">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-bold text-cb-ink sm:text-2xl">Choose your seats</h2>
                        <p class="mt-1 text-sm text-cb-ink/60">
                            {{ $screen->total_rows }} rows · {{ $screen->seats_per_row }} seats per row
                        </p>
                    </div>
                    <p class="text-xs font-medium text-cb-ink/55 lg:hidden">
                        Scroll sideways to view all seats ↔
                    </p>
                </div>

                {{-- Seat map panel --}}
                <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:p-8">
                    {{-- Screen --}}
                    <div class="mx-auto w-full max-w-[650px]">
                        <div class="h-2 rounded-t-[100%] border-t-4 border-blue-500"></div>
                        <p class="mt-3 text-center text-[10px] font-semibold uppercase tracking-[0.3em] text-slate-400">
                            {{ strtoupper($screen->name) }}
                        </p>
                        <p class="mt-1 text-center text-xs text-cb-ink/50">Front of auditorium</p>
                    </div>

                    {{-- Seat grid --}}
                    <div class="mt-8 w-full overflow-x-auto pb-4">
                        <div id="seat-grid" role="group" aria-label="Seat map" class="mx-auto flex w-max flex-col gap-3 px-2">
                            @foreach ($rows as $label => $rowSeats)
                                <div class="flex items-center gap-3">
                                    <span class="w-5 shrink-0 text-center text-xs font-semibold text-cb-ink/60">
                                        {{ $label }}
                                    </span>

                                    <div class="flex gap-1.5">
                                        @foreach ($rowSeats as $seat)
                                            <button
                                                type="button"
                                                data-id="{{ $seat['id'] }}"
                                                data-label="{{ $seat['label'] }}"
                                                data-number="{{ $seat['number'] }}"
                                                data-premium="{{ $seat['premium'] ? 1 : 0 }}"
                                                data-state="{{ $seat['state'] }}"
                                                data-price="{{ $seat['price'] }}"
                                            >
                                                {{ str_pad($seat['number'], 2, '0', STR_PAD_LEFT) }}
                                            </button>
                                        @endforeach
                                    </div>

                                    <span class="w-5 shrink-0 text-center text-xs font-semibold text-cb-ink/60">
                                        {{ $label }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Legend --}}
                    <div class="mt-5 flex flex-wrap justify-center gap-x-5 gap-y-3 border-t border-slate-100 pt-5 text-xs text-cb-ink/70">
                        <span class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 rounded border border-slate-300 bg-white"></span>
                            Available
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 rounded border border-amber-300 bg-amber-100"></span>
                            Held
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 rounded border border-slate-300 bg-slate-200"></span>
                            Sold
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 rounded border border-slate-300 bg-slate-100"></span>
                            Disabled
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 rounded border border-blue-600 bg-blue-600"></span>
                            Selected
                        </span>
                    </div>

                    <p class="mt-4 text-center text-xs text-cb-ink/50">
                        Premium seats: {{ $money($showtime->base_price_cents + $surcharge) }} per seat
                    </p>
                </section>

                {{-- Showtime summary --}}
                <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div>
                        <p class="font-semibold text-cb-ink">
                            {{ $showtime->starts_at->format('l, M j · H:i') }}
                        </p>
                        <p class="mt-1 text-sm text-cb-ink/60">
                            {{ $screen->name }} · {{ $screen->total_rows }} rows × {{ $screen->seats_per_row }} seats
                        </p>
                        <p class="mt-1 text-xs text-cb-ink/55">
                            Sales close {{ $closesAt->format('H:i') }}
                        </p>
                    </div>
                    <p class="rounded-full bg-green-50 px-3 py-2 text-xs font-semibold text-green-700">
                        ✓ {{ $available }} seats available
                    </p>
                </section>
            </div>
        </div>
    </section>

    <script>
        (function () {
            const $ = (id) => document.getElementById(id);
            const buttons = Array.from(document.querySelectorAll('#seat-grid button[data-id]'));
            const MAX = @json($max);
            const form = $('hold-form');
            const holdButton = $('hold-button');
            const selected = new Map();

            const BASE = 'h-9 w-9 shrink-0 rounded-lg border font-mono text-[11px] font-semibold leading-none outline-offset-2 transition focus-visible:outline-2 focus-visible:outline-blue-600 sm:h-10 sm:w-10 sm:text-xs';

            const STYLE = {
                available: 'border-slate-300 bg-white text-cb-ink hover:border-blue-500 hover:bg-blue-50',
                premium: 'border-blue-300 bg-blue-50 text-blue-800 hover:border-blue-500 hover:bg-blue-100',
                held: 'cursor-not-allowed border-amber-300 bg-amber-100 text-amber-800',
                sold: 'cursor-not-allowed border-slate-200 bg-slate-200 text-slate-500',
                disabled: 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400',
                selected: 'border-blue-600 bg-blue-600 text-white shadow-sm',
            };

            const MARK = { held: '◷', sold: '•', disabled: '×' };
            const pad = (n) => String(n).padStart(2, '0');
            const money = (c) => '$' + Math.floor(c / 100) + '.' + pad(c % 100);

            function paint(btn) {
                const state = btn.dataset.state;
                const isSelected = selected.has(btn.dataset.id);

                let kind = state === 'available'
                    ? (btn.dataset.premium === '1' ? 'premium' : 'available')
                    : state;

                if (isSelected) kind = 'selected';

                btn.className = BASE + ' ' + STYLE[kind] + (isSelected ? ' ring-2 ring-blue-200' : '');
                btn.textContent = pad(btn.dataset.number) + (isSelected ? '✓' : (MARK[state] || ''));
                btn.disabled = state !== 'available';
                btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                btn.setAttribute(
                    'aria-label',
                    'Seat ' + btn.dataset.label + ', ' +
                    (btn.dataset.premium === '1' ? 'Premium' : 'Standard') + ', ' +
                    (isSelected ? 'selected' : state)
                );
            }

            function render() {
                const list = $('selection-list');
                list.innerHTML = '';

                let total = 0;
                const unitPrices = new Set();

                selected.forEach((btn) => {
                    const price = parseInt(btn.dataset.price, 10);
                    total += price;
                    unitPrices.add(price);

                    const chip = document.createElement('span');
                    chip.className = 'rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 font-medium';
                    chip.textContent = btn.dataset.label + ' · ' +
                        (btn.dataset.premium === '1' ? 'Premium' : 'Standard');

                    list.appendChild(chip);
                });

                const count = selected.size;

                if (!count) {
                    $('selection-total').textContent = 'Not held yet.';
                } else if (unitPrices.size === 1) {
                    $('selection-total').textContent =
                        count + ' × ' + money([...unitPrices][0]) + ' · USD · ' + money(total);
                } else {
                    $('selection-total').textContent = count + ' seats · USD · ' + money(total);
                }

                if (form) {
                    const inputs = $('hold-inputs');
                    inputs.innerHTML = '';

                    selected.forEach((btn) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'seat_ids[]';
                        input.value = btn.dataset.id;
                        inputs.appendChild(input);
                    });

                    holdButton.disabled = count === 0;
                    holdButton.textContent = count
                        ? 'Hold ' + count + (count === 1 ? ' seat' : ' seats') + ' · ' + money(total) + ' →'
                        : 'Select seats';
                }
            }

            buttons.forEach((btn) => {
                paint(btn);

                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;

                    if (selected.has(id)) {
                        selected.delete(id);
                    } else {
                        if (selected.size >= MAX) {
                            alert('You can select up to ' + MAX + ' seats.');
                            return;
                        }

                        selected.set(id, btn);
                    }

                    paint(btn);
                    render();
                });
            });

            render();
        })();
    </script>
</x-layouts.cinebook>