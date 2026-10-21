@extends('layouts.admin')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Programming / Screens</p>
        <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Create screen</h1>
    </div>

    <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
        <p>ⓘ Draft preview · not saved</p>
        <p>Generate the seat grid before saving. Screen names must be unique.</p>
    </div>

    <form method="POST" action="{{ route('admin.screens.store') }}"
          class="flex flex-col gap-6 xl:flex-row xl:items-start">
        @csrf
        <input type="hidden" name="disabled_seats" id="disabled_seats" value="{{ old('disabled_seats') }}">

        {{-- LEFT: generator --}}
        <div class="flex w-full shrink-0 flex-col gap-4 xl:w-[330px]">
            <div class="flex flex-col gap-5 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6">
                <h2 class="text-[22px] font-bold leading-[1.13] text-[#f4f7ff]">Grid generator</h2>

                <label for="name" class="flex min-h-[76px] cursor-text flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                    <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Screen name *</span>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" maxlength="100" required
                           placeholder="Enter a unique screen name"
                           class="w-full bg-transparent text-sm text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]">
                </label>
                @error('name') <p class="-mt-3 text-sm text-red-400">{{ $message }}</p> @enderror

                <label for="total_rows" class="flex min-h-[76px] cursor-text flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                    <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Rows *</span>
                    <input id="total_rows" type="number" name="total_rows" value="{{ old('total_rows', 8) }}" min="1" max="26" required
                           class="w-full bg-transparent text-sm text-[#f4f7ff] outline-none">
                    <span class="text-[13px] leading-[1.7] text-[#a0aec5]">1–26 rows · Letters A–Z</span>
                </label>
                @error('total_rows') <p class="-mt-3 text-sm text-red-400">{{ $message }}</p> @enderror

                <label for="seats_per_row" class="flex min-h-[76px] cursor-text flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                    <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Seats per row *</span>
                    <input id="seats_per_row" type="number" name="seats_per_row" value="{{ old('seats_per_row', 12) }}" min="1" max="30" required
                           class="w-full bg-transparent text-sm text-[#f4f7ff] outline-none">
                    <span class="text-[13px] leading-[1.7] text-[#a0aec5]">1–30 numbered seats</span>
                </label>
                @error('seats_per_row') <p class="-mt-3 text-sm text-red-400">{{ $message }}</p> @enderror

                <label for="premium_rows" class="flex min-h-[76px] cursor-text flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                    <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Premium rows</span>
                    <input id="premium_rows" type="text" name="premium_rows" value="{{ old('premium_rows', 'G, H') }}"
                           placeholder="G, H"
                           class="w-full bg-transparent text-sm text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]">
                    <span class="text-[13px] leading-[1.7] text-[#a0aec5]">Optional row letters within the generated grid</span>
                </label>
                @error('premium_rows') <p class="-mt-3 text-sm text-red-400">{{ $message }}</p> @enderror

                <button type="button" id="generate-preview"
                        class="self-start rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">
                    Generate preview
                </button>

                <div class="text-sm leading-[1.6] text-[#a0aec5]">
                    <p id="summary-line">• 96 seats · 24 Premium</p>
                    <p class="mt-4">Preview seats to toggle them disabled before saving. Pricing is set per showtime; Premium surcharge comes from Settings.</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">
                    Create screen
                </button>
                <a href="{{ route('admin.screens.index') }}"
                   class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:bg-[#1e2f4d]">
                    Cancel
                </a>
            </div>
        </div>

        {{-- RIGHT: seat grid preview --}}
        <div class="flex min-w-0 flex-1 flex-col gap-4">
            <h2 class="text-2xl font-bold leading-[1.13] text-[#f4f7ff]">Seat grid preview</h2>

            <div class="flex flex-col items-center gap-[18px] rounded-2xl bg-[#0c1424] p-6">
                <div class="flex h-7 w-full max-w-[560px] items-start justify-center rounded-[60px] border-t-[3px] border-[#347bff]">
                    <p id="screen-label" class="text-[10px] leading-[1.45] tracking-[0.35em] text-[#74849f]">SCREEN</p>
                </div>
                <p class="text-[11px] leading-[1.45] text-[#74849f]">Front of auditorium</p>

                <div class="w-full overflow-x-auto">
                    <div id="seat-grid" role="group" aria-label="Seat grid preview" class="mx-auto flex w-max flex-col gap-[14px]"></div>
                </div>

                <div class="w-full text-sm leading-[1.6] text-[#a0aec5]">
                    <p id="premium-line"></p>
                    <p class="mt-3">□ Available &nbsp; ◷ Held &nbsp; • Sold &nbsp; × Disabled &nbsp; ✓ Your selection Outlined G7 demonstrates keyboard focus.</p>
                    <p id="disabled-line" class="mt-3"></p>
                </div>
            </div>

            <p id="draft-line" class="text-[13px] leading-[1.45] text-[#a0aec5]"></p>

            <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
                <p>ⓘ Lock dimensions on first show</p>
                <p>After show history exists, the row count and seats per row can’t be changed. Individual eligible seats can still be enabled or disabled.</p>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const $ = (id) => document.getElementById(id);
            const nameEl = $('name'), rowsEl = $('total_rows'), perRowEl = $('seats_per_row'), premiumEl = $('premium_rows');
            const grid = $('seat-grid'), hidden = $('disabled_seats');

            const BASE = 'h-10 w-10 rounded-md border font-mono text-[12px] leading-none outline-offset-2 focus-visible:outline-2 focus-visible:outline-white';
            const STYLE = {
                available: 'border-[#2c3f63] bg-[#1b2a45] text-[#f4f7ff] hover:border-[#347bff]',
                premium:   'border-[#5b9bff] bg-[#1b2a45] text-[#f4f7ff] hover:bg-[#223657]',
                disabled:  'border-[#283750] bg-[#080e1b] text-[#74849f]',
            };

            let disabled = new Set((hidden.value || '').split(',').map((s) => s.trim().toUpperCase()).filter(Boolean));

            const clamp = (n, min, max) => Math.min(Math.max(n, min), max);

            // 0 => A, 25 => Z, 26 => AA (same logic as the PHP GenerateSeatsAction)
            function rowLabel(index) {
                let label = '';
                index++;
                while (index > 0) {
                    index--;
                    label = String.fromCharCode(65 + (index % 26)) + label;
                    index = Math.floor(index / 26);
                }
                return label;
            }

            function premiumSet() {
                return new Set(premiumEl.value.split(',').map((s) => s.trim().toUpperCase()).filter(Boolean));
            }

            function paintSeat(btn) {
                const key = btn.dataset.seat;
                const isDisabled = disabled.has(key);
                const kind = isDisabled ? 'disabled' : (btn.dataset.premium === '1' ? 'premium' : 'available');
                btn.className = BASE + ' ' + STYLE[kind];
                btn.textContent = String(btn.dataset.number).padStart(2, '0') + (isDisabled ? '×' : '');
                btn.setAttribute('aria-pressed', isDisabled ? 'true' : 'false');
                btn.setAttribute('aria-label', 'Seat ' + key + ', ' + (btn.dataset.premium === '1' ? 'Premium' : 'Standard') + ', ' + (isDisabled ? 'Disabled' : 'Available'));
            }

            function sortedDisabled() {
                const parse = (k) => { const m = k.match(/^([A-Z]+)(\d+)$/); return [m[1], parseInt(m[2], 10)]; };
                return [...disabled].sort((a, b) => {
                    const [ra, na] = parse(a), [rb, nb] = parse(b);
                    return ra.length - rb.length || ra.localeCompare(rb) || na - nb;
                });
            }

            function updateSummary() {
                const rows = clamp(parseInt(rowsEl.value, 10) || 0, 0, 26);
                const per = clamp(parseInt(perRowEl.value, 10) || 0, 0, 30);
                const total = rows * per;
                const labels = Array.from({ length: rows }, (_, i) => rowLabel(i));
                const prem = [...premiumSet()].filter((r) => labels.includes(r));
                const list = sortedDisabled();
                const bookable = total - list.length;

                $('summary-line').textContent = '• ' + total + ' seats · ' + (prem.length * per) + ' Premium';
               const idx = prem.map((r) => labels.indexOf(r)).sort((a, b) => a - b);
const contiguous = idx.length > 1 && idx[idx.length - 1] - idx[0] === idx.length - 1;
const premLabel = contiguous ? labels[idx[0]] + '–' + labels[idx[idx.length - 1]] : prem.join(', ');
$('premium-line').textContent = prem.length ? premLabel + ' · Premium seats · $12 per seat' : 'No premium rows';
                $('disabled-line').textContent = 'Disabled: ' + (list.length ? list.join(', ') : 'none') + ' · ' + bookable + ' bookable / ' + total + ' total';
                $('draft-line').textContent = 'Draft preview · ' + (list.length ? list.join(', ') + ' disabled' : 'no seats disabled') + ' · ' + bookable + ' bookable seats.';
                $('screen-label').textContent = (nameEl.value.trim() || 'Screen').toUpperCase();
                hidden.value = list.join(',');
            }

            function render() {
                const rows = clamp(parseInt(rowsEl.value, 10) || 0, 0, 26);
                const per = clamp(parseInt(perRowEl.value, 10) || 0, 0, 30);
                const prem = premiumSet();

                // forget disabled seats that no longer exist in the grid
                const valid = new Set();
                for (let r = 0; r < rows; r++) for (let n = 1; n <= per; n++) valid.add(rowLabel(r) + n);
                disabled = new Set([...disabled].filter((k) => valid.has(k)));

                grid.innerHTML = '';
                for (let r = 0; r < rows; r++) {
                    const label = rowLabel(r);
                    const line = document.createElement('div');
                    line.className = 'flex items-center gap-3';

                    const left = document.createElement('span');
                    left.className = 'w-6 text-center text-xs text-[#f4f7ff]';
                    left.textContent = label;

                    const seats = document.createElement('div');
                    seats.className = 'flex gap-[5px]';

                    for (let n = 1; n <= per; n++) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.dataset.seat = label + n;
                        btn.dataset.number = n;
                        btn.dataset.premium = prem.has(label) ? '1' : '0';
                        paintSeat(btn);
                        btn.addEventListener('click', () => {
                            const key = btn.dataset.seat;
                            disabled.has(key) ? disabled.delete(key) : disabled.add(key);
                            paintSeat(btn);
                            updateSummary();
                        });
                        seats.appendChild(btn);
                    }

                    const right = left.cloneNode(true);
                    line.append(left, seats, right);
                    grid.appendChild(line);
                }
                updateSummary();
            }

            [nameEl, rowsEl, perRowEl, premiumEl].forEach((el) => el.addEventListener('input', render));
            $('generate-preview').addEventListener('click', render);
            render();
        })();
    </script>
@endsection