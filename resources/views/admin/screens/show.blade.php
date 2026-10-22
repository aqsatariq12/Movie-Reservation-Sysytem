@php
    $disabledKeys = $seats->flatten(1)->where('is_disabled', true)
        ->map(fn ($s) => $s->row_label . $s->seat_number)->implode(',');
    $bookable = $screen->seats_count - $screen->disabled_seats_count;
@endphp

@extends('layouts.admin')

@section('content')
    <div class="flex items-center gap-6">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Programming / Screens</p>
            <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">{{ $screen->name }} · Seat management</h1>
        </div>
        <button type="submit" form="seat-form"
                class="shrink-0 rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">
            Save seat changes
        </button>
    </div>

    <form id="seat-form" method="POST" action="{{ route('admin.screens.sync-seats', $screen) }}">
        @csrf

        <input type="hidden" name="disabled_seats" id="disabled_seats" value="{{ $disabledKeys }}">
    </form>

    @if (session('status'))
        <div class="rounded-lg border border-green-700 bg-green-900/40 p-4 text-sm text-green-200">{{ session('status') }}</div>
    @endif

    <div class="flex items-center gap-4">
        <p class="flex-1 text-xs text-[#f0c47a]">◷ Dimensions locked</p>
        <p id="top-counts" class="flex-1 text-[13px] leading-[1.45] text-[#a0aec5]">
            {{ $screen->showtimes_count > 0 ? 'Show history exists · ' : '' }}{{ $screen->seats_count }} total · {{ $bookable }} bookable · {{ $screen->premium_seats_count }} Premium
        </p>
    </div>

    <div class="flex flex-col gap-6 xl:flex-row xl:items-start">
        {{-- LEFT: seat map --}}
        <div class="flex min-w-0 flex-1 flex-col gap-4">
            <div class="flex flex-col items-center gap-[18px] rounded-2xl bg-[#0c1424] p-6">
                <div class="flex h-7 w-full max-w-[560px] items-start justify-center rounded-[60px] border-t-[3px] border-[#347bff]">
                    <p class="text-[10px] leading-[1.45] tracking-[0.35em] text-[#74849f]">{{ strtoupper($screen->name) }}</p>
                </div>
                <p class="text-[11px] leading-[1.45] text-[#74849f]">Front of auditorium</p>

                <div class="w-full overflow-x-auto">
                    <div id="seat-grid" role="group" aria-label="Seat map" class="mx-auto flex w-max flex-col gap-[14px]">
                        @foreach ($seats as $label => $rowSeats)
                            <div class="flex items-center gap-3">
                                <span class="w-6 text-center text-xs text-[#f4f7ff]">{{ $label }}</span>
                                <div class="flex gap-[5px]">
                                    @foreach ($rowSeats as $seat)
                                        <button type="button"
                                                data-seat="{{ $seat->row_label . $seat->seat_number }}"
                                                data-number="{{ $seat->seat_number }}"
                                                data-premium="{{ $seat->type === 'premium' ? 1 : 0 }}"
                                                data-disabled="{{ $seat->is_disabled ? 1 : 0 }}"
                                                data-reserved="{{ $reserved->get($seat->id) }}">
                                            {{ str_pad($seat->seat_number, 2, '0', STR_PAD_LEFT) }}
                                        </button>
                                    @endforeach
                                </div>
                                <span class="w-6 text-center text-xs text-[#f4f7ff]">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="w-full text-sm leading-[1.6] text-[#a0aec5]">
                    {{-- TODO: replace the fixed "$12" with the real premium price from Settings --}}
                    <p>{{ $screen->premiumRowsSummary() ?: 'No premium rows' }}{{ $screen->premium_seats_count ? ' · Premium seats · $12 per seat' : '' }}</p>
                    <p class="mt-3">□ Available &nbsp; ◷ Held &nbsp; • Sold &nbsp; × Disabled &nbsp; ✓ Your selection Outlined G7 demonstrates keyboard focus.</p>
                    <p id="disabled-line" class="mt-3"></p>
                </div>
            </div>

            @if ($errors->has('seat_title'))
                <div class="rounded-lg bg-[#3d222e] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
                    <p>! {{ $errors->first('seat_title') }}</p>
                    <p>{{ $errors->first('seat_body') }}</p>
                </div>
            @endif

            @error('disabled_seats')
                <div class="rounded-lg bg-[#3d222e] p-4 text-sm text-[#f4f7ff]">{{ $message }}</div>
            @enderror
        </div>

        {{-- RIGHT: panels --}}
        <div class="flex w-full shrink-0 flex-col gap-4 xl:w-[310px]">
            <form method="POST" action="{{ route('admin.screens.update', $screen) }}"
                  class="flex flex-col gap-5 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6">
                @csrf
                @method('PUT')
                <h2 class="text-[22px] font-bold leading-[1.13] text-[#f4f7ff]">Screen settings</h2>

                <label for="name" class="flex min-h-[76px] cursor-text flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                    <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Screen name</span>
                    <input id="name" type="text" name="name" value="{{ old('name', $screen->name) }}" maxlength="100" required
                           class="w-full bg-transparent text-[13px] text-[#a0aec5] outline-none">
                </label>
                @error('name') <p class="-mt-3 text-sm text-red-400">{{ $message }}</p> @enderror

                <div class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] text-[13px] leading-[1.7] text-[#a0aec5]">
                    <span class="uppercase">Rows · Locked</span>
                    <span>{{ $screen->total_rows }} · {{ $screen->rowRange() }} 🔒</span>
                </div>

                <div class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] text-[13px] leading-[1.7] text-[#a0aec5]">
                    <span class="uppercase">Seats per row · Locked</span>
                    <span>{{ $screen->seats_per_row }} 🔒</span>
                </div>

                <div class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] text-[13px] leading-[1.7] text-[#a0aec5]">
                    <span class="uppercase">Premium rows</span>
                    <span>{{ implode(', ', $screen->premiumRowLabels()) ?: 'None' }}</span>
                </div>

                <p class="text-xs leading-[1.45] text-[#a0aec5]">
                    Select an eligible seat to toggle Disabled. The preview marks disabled seats with ×, not only color.
                </p>

                <button type="submit"
                        class="self-start rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">
                    Save changes
                </button>
            </form>

            <div class="flex flex-col gap-5 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6">
                <div class="text-sm leading-[1.6] text-[#a0aec5]">
                    <p id="sel-title">Selected seat · none</p>
                    <p class="mt-4">Type &nbsp;·&nbsp; <span id="sel-type">–</span></p>
                    <p class="mt-4">Future reservations &nbsp;·&nbsp; <span id="sel-res">–</span></p>
                    <p class="mt-4">Disabled &nbsp;·&nbsp; <span id="sel-disabled">–</span></p>
                </div>
                <button type="button" id="sel-btn" disabled
                        class="self-start rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:bg-[#1e2f4d] disabled:cursor-not-allowed disabled:text-[#74849f] disabled:hover:bg-[#17243b]">
                    Select a seat
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const $ = (id) => document.getElementById(id);
            const buttons = Array.from(document.querySelectorAll('#seat-grid button[data-seat]'));
            const hidden = $('disabled_seats');
            const total = buttons.length;
            const premiumTotal = buttons.filter((b) => b.dataset.premium === '1').length;
            const hasHistory = @json($screen->showtimes_count > 0);
            const disabled = new Set(buttons.filter((b) => b.dataset.disabled === '1').map((b) => b.dataset.seat));
            let selected = null;

            const BASE = 'h-10 w-10 rounded-md border font-mono text-[12px] leading-none outline-offset-2 focus-visible:outline-2 focus-visible:outline-white';
            const STYLE = {
                available: 'border-[#2c3f63] bg-[#1b2a45] text-[#f4f7ff] hover:border-[#347bff]',
                premium:   'border-[#5b9bff] bg-[#1b2a45] text-[#f4f7ff] hover:bg-[#223657]',
                held:      'border-[#6b5128] bg-[#3a2d1d] text-[#f0c47a]',
                selected:  'border-[#347bff] bg-[#347bff] text-white',
                disabled:  'border-[#283750] bg-[#080e1b] text-[#74849f]',
            };
            const MARK = { sold: '•', held: '◷' };

            function paint(btn) {
                const key = btn.dataset.seat;
                const isDisabled = disabled.has(key);
                const isSelected = selected === btn;

                let kind = 'available';
                if (btn.dataset.premium === '1') kind = 'premium';
                if (btn.dataset.reserved === 'held') kind = 'held';
                if (isSelected) kind = 'selected';
                if (isDisabled) kind = 'disabled';

                btn.className = BASE + ' ' + STYLE[kind] + (isSelected ? ' outline outline-2 outline-white' : '');
                const mark = isDisabled ? '×' : (isSelected ? '✓' : (MARK[btn.dataset.reserved] || ''));
                btn.textContent = String(btn.dataset.number).padStart(2, '0') + mark;
                btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                btn.setAttribute('aria-label', 'Seat ' + key + ', ' + (btn.dataset.premium === '1' ? 'Premium' : 'Standard') + ', ' + (isDisabled ? 'Disabled' : 'Available'));
            }

            function sortedDisabled() {
                const parse = (k) => { const m = k.match(/^([A-Z]+)(\d+)$/); return [m[1], parseInt(m[2], 10)]; };
                return [...disabled].sort((a, b) => {
                    const [ra, na] = parse(a), [rb, nb] = parse(b);
                    return ra.length - rb.length || ra.localeCompare(rb) || na - nb;
                });
            }

            function reservedText(btn) {
                if (btn.dataset.reserved === 'sold') return '1 confirmed';
                if (btn.dataset.reserved === 'held') return '1 held';
                return 'None';
            }

            function updateSummary() {
                const list = sortedDisabled();
                const bookable = total - list.length;
                hidden.value = list.join(',');
                $('disabled-line').textContent = 'Disabled: ' + (list.length ? list.join(', ') : 'none') + ' · ' + bookable + ' bookable / ' + total + ' total';
                $('top-counts').textContent = (hasHistory ? 'Show history exists · ' : '') + total + ' total · ' + bookable + ' bookable · ' + premiumTotal + ' Premium';
            }

            function updatePanel() {
                const btn = $('sel-btn');
                if (!selected) {
                    $('sel-title').textContent = 'Selected seat · none';
                    $('sel-type').textContent = '–';
                    $('sel-res').textContent = '–';
                    $('sel-disabled').textContent = '–';
                    btn.textContent = 'Select a seat';
                    btn.disabled = true;
                    return;
                }
                const key = selected.dataset.seat;
                const isDisabled = disabled.has(key);
                $('sel-title').textContent = 'Selected seat · ' + key;
                $('sel-type').textContent = selected.dataset.premium === '1' ? 'Premium' : 'Standard';
                $('sel-res').textContent = reservedText(selected);
                $('sel-disabled').textContent = isDisabled ? 'Yes' : 'No';
                btn.textContent = (isDisabled ? 'Enable ' : 'Disable ') + key;
                // a seat with an active hold or future booking cannot be disabled
                btn.disabled = !isDisabled && !!selected.dataset.reserved;
            }

            buttons.forEach((btn) => {
                paint(btn);
                btn.addEventListener('click', () => {
                    const previous = selected;
                    selected = btn;
                    if (previous) paint(previous);
                    paint(btn);
                    updatePanel();
                });
            });

            $('sel-btn').addEventListener('click', () => {
                if (!selected) return;
                const key = selected.dataset.seat;
                disabled.has(key) ? disabled.delete(key) : disabled.add(key);
                paint(selected);
                updatePanel();
                updateSummary();
            });

            updateSummary();
            updatePanel();
        })();
    </script>
@endsection