@php
    $money = fn (int $c) => \App\Models\Showtime::money($c);
    $closesAt = $showtime->starts_at->copy()->subMinutes($cutoff);
    $isVerified = auth()->check() && auth()->user()->hasVerifiedEmail();
@endphp

<x-layouts::customer :title="$movie->title . ' · Select seats'">
    <div class="flex flex-col gap-4 md:gap-6">
        <p class="text-xs uppercase tracking-wide text-[#a0aec5]">
            <span class="md:hidden">01 Seats → 02 Checkout → 03 Tickets</span>
            <span class="hidden md:inline">01 Select seats → 02 Checkout → 03 Your tickets</span>
        </p>
        <h1 class="text-[28px] font-bold leading-[1.15] md:text-[40px]">
            <span class="md:hidden">Choose your seats</span>
            <span class="hidden md:inline">A great night starts with a great seat</span>
        </h1>

        <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
            @foreach ($days as $date => $dayShows)
                @php $first = $dayShows->first(); @endphp
                <a href="{{ route('showtimes.seats', $first) }}"
                   class="pb-1 {{ $first->starts_at->isSameDay($showtime->starts_at) ? 'border-b-2 border-[#347bff] text-[#87beff]' : 'text-[#a0aec5] hover:text-white' }}">
                    {{ $first->starts_at->format('D · M j') }}
                </a>
            @endforeach
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-green-700 bg-green-900/40 p-4 text-sm text-green-200">{{ session('status') }}</div>
    @endif

    <div class="flex flex-col gap-6 xl:grid xl:grid-cols-[360px_minmax(0,1fr)] xl:grid-rows-[auto_1fr] xl:items-start">

        {{-- 1. Movie and showtime --}}
        <div class="flex flex-col gap-4 rounded-2xl border border-[#283750] bg-[#101a2d] p-5 xl:col-start-1 xl:row-start-1 xl:border-0 xl:bg-transparent xl:p-0">
            {{-- mobile: compact text --}}
            <div class="text-sm leading-[1.6] text-[#a0aec5] md:hidden">
                <p class="text-lg text-[#f4f7ff]">{{ $movie->title }}</p>
                <p>{{ $showtime->starts_at->format('D, M j · H:i') }} · {{ $screen->name }} {{ $movie->rating }} · {{ $movie->duration_minutes }} min · Downtown</p>
            </div>

            {{-- desktop: poster and details --}}
            <div class="hidden gap-4 md:flex">
                @if ($movie->poster_path)
                    <img src="{{ asset('storage/' . $movie->poster_path) }}" alt="{{ $movie->title }} poster"
                         class="h-[170px] w-[95px] rounded-lg object-cover">
                @else
                    <div class="flex h-[170px] w-[95px] shrink-0 items-center justify-center rounded-lg bg-gradient-to-b from-[#1b2a45] to-[#0c1424] text-2xl text-[#87beff]">
                        {{ \Illuminate\Support\Str::of($movie->title)->substr(0, 1) }}
                    </div>
                @endif
                <div class="min-w-0 text-sm text-[#a0aec5]">
                    <p class="text-xs uppercase">• {{ $screen->name }}</p>
                    <p class="mt-2 text-[26px] font-bold leading-[1.2] text-[#f4f7ff]">{{ $movie->title }}</p>
                    <p class="mt-3">{{ $movie->rating }} · {{ $movie->duration_minutes }} min {{ $genres->implode(' · ') }}</p>
                </div>
            </div>

            <p class="hidden text-base md:block">{{ $showtime->starts_at->format('l, F j, Y') }}</p>

            <div class="flex flex-wrap gap-3">
                @foreach ($sameDay as $s)
                    @php $isClosed = $s->starts_at->copy()->subMinutes($cutoff)->lte($now); @endphp
                    @if ($isClosed)
                        <span class="rounded-lg border border-[#283750] bg-[#101a2d] px-4 py-3 text-sm text-[#74849f]">{{ $s->starts_at->format('H:i') }} · Closed</span>
                    @else
                        <a href="{{ route('showtimes.seats', $s) }}"
                           class="rounded-lg border px-4 py-3 text-sm {{ $s->id === $showtime->id ? 'border-[#347bff] bg-[#347bff] text-white' : 'border-[#283750] bg-[#17243b] text-white hover:bg-[#1e2f4d]' }}">
                            {{ $s->starts_at->format('H:i') }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- 2. Seat map --}}
        <div class="flex min-w-0 flex-col gap-4 xl:col-start-2 xl:row-span-2 xl:row-start-1">
            <p class="text-xs text-[#87beff] lg:hidden">Seat map scrolls horizontally ↔ · {{ $screen->total_rows }} rows × {{ $screen->seats_per_row }}</p>

            <div class="flex flex-col items-center gap-[18px] rounded-2xl border border-[#283750] bg-[#0c1424] p-4 md:p-6">
                <div class="flex h-7 w-full max-w-[560px] items-start justify-center rounded-[60px] border-t-[3px] border-[#347bff]">
                    <p class="text-[10px] leading-[1.45] tracking-[0.35em] text-[#74849f]">{{ strtoupper($screen->name) }}</p>
                </div>
                <p class="text-[11px] text-[#74849f]">Front of auditorium</p>

                <div class="w-full overflow-x-auto pb-2">
                    <div id="seat-grid" role="group" aria-label="Seat map" class="mx-auto flex w-max flex-col gap-[14px]">
                        @foreach ($rows as $label => $rowSeats)
                            <div class="flex items-center gap-3">
                                <span class="w-6 text-center text-xs">{{ $label }}</span>
                                <div class="flex gap-[5px]">
                                    @foreach ($rowSeats as $seat)
                                        <button type="button"
                                                data-id="{{ $seat['id'] }}"
                                                data-label="{{ $seat['label'] }}"
                                                data-number="{{ $seat['number'] }}"
                                                data-premium="{{ $seat['premium'] ? 1 : 0 }}"
                                                data-state="{{ $seat['state'] }}"
                                                data-price="{{ $seat['price'] }}">
                                            {{ str_pad($seat['number'], 2, '0', STR_PAD_LEFT) }}
                                        </button>
                                    @endforeach
                                </div>
                                <span class="w-6 text-center text-xs">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="w-full text-sm leading-[1.6] text-[#a0aec5]">
                    @if ($screen->premiumRowsSummary())
                        <p>{{ $screen->premiumRowsSummary() }} · Premium seats · {{ $money($showtime->base_price_cents + $surcharge) }} per seat</p>
                    @endif
                    <p class="mt-3 hidden md:block">□ Available &nbsp; ◷ Held &nbsp; • Sold &nbsp; × Disabled &nbsp; ✓ Your selection Outlined G7 demonstrates keyboard focus.</p>
                    <p class="mt-3 text-xs md:hidden">□ Available · ◷ Held · • Sold · × Disabled · ✓ Selected · G7 focus outline</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#283750] bg-[#101a2d] p-5 text-sm text-[#a0aec5] md:p-6">
                <div>
                    <p>{{ $showtime->starts_at->format('l, M j · H:i') }}</p>
                    <p class="mt-3">{{ $screen->name }} · {{ $screen->total_rows }} rows × {{ $screen->seats_per_row }} seats · Sales close {{ $closesAt->format('H:i') }}</p>
                </div>
                <p class="text-xs text-green-400">✓ {{ $available }} seats available</p>
            </div>
        </div>

        {{-- 3. Selection --}}
        <div class="flex flex-col gap-4 xl:col-start-1 xl:row-start-2">
            <div class="flex flex-col gap-4 rounded-[14px] border border-[#283750] bg-[#101a2d] p-5 md:p-6">
                <h2 class="hidden text-[22px] font-bold md:block">Your selection</h2>
                <h2 id="selection-title-mobile" class="text-[22px] font-bold md:hidden">Select your seats</h2>

                <div id="selection-list" class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-[#87beff]"></div>
                <p id="selection-total" class="text-sm text-[#a0aec5]">Not held yet.</p>
                <p class="text-sm leading-[1.6] text-[#a0aec5]">
                    <span class="hidden md:inline">Standard {{ $money($showtime->base_price_cents) }} · Premium {{ $money($showtime->base_price_cents + $surcharge) }} Select 1–{{ $max }} seats. Not held yet.</span>
                    <span class="md:hidden">Premium includes {{ $money($surcharge) }} surcharge. Select 1–{{ $max }} seats. Your hold hasn’t started.</span>
                </p>

                @error('seats')
                    <div class="rounded-lg bg-[#3d222e] p-3 text-sm text-[#f4f7ff]">! {{ $message }}</div>
                @enderror

                @if ($closed)
                    <p class="rounded-lg bg-[#3a2d1d] p-3 text-sm text-[#f0c47a]">Sales for this showtime have closed.</p>
                @elseif ($isVerified)
                    <form id="hold-form" method="POST" action="{{ route('showtimes.holds.store', $showtime) }}">
                        @csrf
                        <div id="hold-inputs"></div>
                        <button id="hold-button" type="submit" disabled
                                class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff] disabled:cursor-not-allowed disabled:border-[#283750] disabled:bg-[#17243b] disabled:text-[#74849f]">
                            Select seats
                        </button>
                    </form>
                @endif
            </div>

            @guest
                <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6]">
                    <p>ⓘ Sign in to hold seats</p>
                    <p>If you’re browsing as a guest, sign in first. We’ll bring you back to this showtime. Your email must be verified.</p>
                    <a href="{{ route('login') }}" class="mt-3 inline-block rounded-lg border border-[#347bff] bg-[#347bff] px-4 py-2 text-white hover:bg-[#4a8bff]">Sign in</a>
                </div>
            @else
                @unless ($isVerified)
                    <div class="rounded-lg bg-[#3a2d1d] p-4 text-sm text-[#f0c47a]">Please verify your email address before holding seats.</div>
                @endunless
            @endguest

            <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] md:hidden">
                <p>ⓘ {{ $holdMinutes }} minutes, after you hold</p>
                <p>A signed-in, email-verified account is required. Guest sign-in returns you to this showtime.</p>
            </div>

            <p class="hidden text-xs leading-[1.5] text-[#a0aec5] md:block">
                Your {{ $holdMinutes }}-minute hold starts only after you choose Hold seats.
                All seats are held together or none are.
            </p>
            <p class="text-xs leading-[1.5] text-[#a0aec5] md:hidden">
                Sales close at {{ $closesAt->format('H:i') }}. All selected seats are held together or none are.
            </p>
        </div>
    </div>

    <script>
        (function () {
            const $ = (id) => document.getElementById(id);
            const buttons = Array.from(document.querySelectorAll('#seat-grid button[data-id]'));
            const MAX = @json($max);
            const form = $('hold-form');
            const holdButton = $('hold-button');
            const selected = new Map(); // seat id -> button, in the order the customer picked them

            const BASE = 'h-10 w-10 rounded-md border font-mono text-[12px] leading-none outline-offset-2 focus-visible:outline-2 focus-visible:outline-white';
            const STYLE = {
                available: 'border-[#2c3f63] bg-[#1b2a45] text-[#f4f7ff] hover:border-[#347bff]',
                premium:   'border-[#5b9bff] bg-[#1b2a45] text-[#f4f7ff] hover:bg-[#223657]',
                held:      'border-[#6b5128] bg-[#3a2d1d] text-[#f0c47a] cursor-not-allowed',
                sold:      'border-[#283750] bg-[#101a2d] text-[#74849f] cursor-not-allowed',
                disabled:  'border-[#283750] bg-[#080e1b] text-[#74849f] cursor-not-allowed',
                selected:  'border-[#347bff] bg-[#347bff] text-white',
            };
            const MARK = { held: '◷', sold: '•', disabled: '×' };
            const pad = (n) => String(n).padStart(2, '0');
            const money = (c) => '$' + Math.floor(c / 100) + '.' + pad(c % 100);

            function paint(btn) {
                const state = btn.dataset.state;
                const isSelected = selected.has(btn.dataset.id);
                let kind = state === 'available' ? (btn.dataset.premium === '1' ? 'premium' : 'available') : state;
                if (isSelected) kind = 'selected';

                btn.className = BASE + ' ' + STYLE[kind] + (isSelected ? ' outline outline-2 outline-white' : '');
                btn.textContent = pad(btn.dataset.number) + (isSelected ? '✓' : (MARK[state] || ''));
                btn.disabled = state !== 'available';
                btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                btn.setAttribute('aria-label', 'Seat ' + btn.dataset.label + ', ' + (btn.dataset.premium === '1' ? 'Premium' : 'Standard') + ', ' + (isSelected ? 'selected' : state));
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
                    chip.textContent = '• ' + btn.dataset.label + ' · ' + (btn.dataset.premium === '1' ? 'Premium' : 'Standard');
                    list.appendChild(chip);
                });

                const count = selected.size;
                $('selection-title-mobile').textContent = count
                    ? count + (count === 1 ? ' seat' : ' seats') + ' selected'
                    : 'Select your seats';

                if (!count) {
                    $('selection-total').textContent = 'Not held yet.';
                } else if (unitPrices.size === 1) {
                    $('selection-total').textContent = count + ' × ' + money([...unitPrices][0]) + ' · USD · ' + money(total);
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
</x-layouts::customer>