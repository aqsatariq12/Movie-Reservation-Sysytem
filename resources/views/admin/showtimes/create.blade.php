@extends('layouts.admin')

@section('content')

<div>
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Programming / Showtimes</p>
        <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Create showtime</h1>
    </div>

    <form method="POST" action="{{ route('admin.showtimes.store') }}" class="flex flex-col gap-6 xl:flex-row xl:items-start">
        @csrf

        <div class="flex min-w-0 flex-1 flex-col gap-4 rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
            <h2 class="text-[22px] font-bold leading-[1.13] text-[#f4f7ff]">Showtime details</h2>

            <label for="movie_id" class="flex flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Movie *</span>
                <select id="movie_id" name="movie_id" required class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                    <option value="">Choose a movie</option>
                    @foreach ($movies as $movie)
                        <option value="{{ $movie->id }}" data-duration="{{ $movie->duration_minutes }}" @selected((int) old('movie_id') === $movie->id)>
                            {{ $movie->title }} · {{ $movie->duration_minutes }} min
                        </option>
                    @endforeach
                </select>
            </label>
            @error('movie_id') <p class="-mt-2 text-sm text-red-400">{{ $message }}</p> @enderror

            <label for="screen_id" class="flex flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Screen *</span>
                <select id="screen_id" name="screen_id" required class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                    <option value="">Choose a screen</option>
                    @foreach ($screens as $screen)
                        <option value="{{ $screen->id }}" @selected((int) old('screen_id') === $screen->id)>
                            {{ $screen->name }} · {{ $screen->bookable_seats_count }} bookable seats
                        </option>
                    @endforeach
                </select>
            </label>
            @error('screen_id') <p class="-mt-2 text-sm text-red-400">{{ $message }}</p> @enderror

            <div class="grid gap-4 md:grid-cols-2">
                <label for="show_date" class="flex flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                    <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Show date *</span>
                    <input id="show_date" type="date" name="show_date" value="{{ old('show_date') }}" required
                           class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none [color-scheme:dark]">
                </label>

                <label for="start_time" class="flex flex-col rounded-lg border {{ $errors->has('start_time') ? 'border-red-400' : 'border-[#283750]' }} bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                    <span class="text-[13px] uppercase leading-[1.7] {{ $errors->has('start_time') ? 'text-red-400' : 'text-[#a0aec5]' }}">Start time *</span>
                    <input id="start_time" type="time" name="start_time" value="{{ old('start_time') }}" required
                           class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none [color-scheme:dark]">
                </label>
            </div>
            @error('show_date') <p class="-mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
            @error('start_time') <p class="-mt-2 text-[13px] text-red-400">{{ $message }}</p> @enderror

            <label for="base_price" class="flex flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] focus-within:border-[#347bff]">
                <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">Base price · USD *</span>
                <input id="base_price" type="text" inputmode="decimal" name="base_price" value="{{ old('base_price', '10.00') }}" required
                       class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none">
                <span class="text-[13px] leading-[1.7] text-[#a0aec5]">
                    Standard price per seat. Premium adds the current {{ \App\Models\Showtime::money($surcharge) }} surcharge.
                </span>
            </label>
            @error('base_price') <p class="-mt-2 text-sm text-red-400">{{ $message }}</p> @enderror

            <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
                <p id="end-title">ⓘ Computed end: choose a movie, date and time</p>
                <p id="end-detail">End time is calculated, not editable.</p>
            </div>

            @error('showtime_error')
                <div class="rounded-lg bg-[#3d222e] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
                    <p>! Showtime not saved</p>
                    <p>{{ $message }}</p>
                </div>
            @enderror

            <div class="flex items-center gap-4">
                <button type="submit" class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">Create showtime</button>
                <a href="{{ route('admin.showtimes.index') }}" class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:bg-[#1e2f4d]">Cancel</a>
            </div>
        </div>

        <div class="flex w-full shrink-0 flex-col gap-4 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6 text-sm leading-[1.6] text-[#a0aec5] xl:w-[310px]">
            <p>Pricing preview</p>
            <p>Standard &nbsp;·&nbsp; <span id="price-standard">—</span></p>
            <p>Premium &nbsp;·&nbsp; <span id="price-premium">—</span></p>
            <p>Existing booking amounts are never recalculated.</p>
        </div>
    </form>

    <script>
        (function () {
            const $ = (id) => document.getElementById(id);
            const TURNAROUND = @json(\App\Models\Showtime::TURNAROUND_MINUTES);
            const SURCHARGE = @json($surcharge);

            const movieEl = $('movie_id'), dateEl = $('show_date'), timeEl = $('start_time'), priceEl = $('base_price');
            const pad = (n) => String(n).padStart(2, '0');

            function toCents(value) {
                const m = /^(\d{1,4})(?:\.(\d{1,2}))?$/.exec(value.trim());
                return m ? parseInt(m[1], 10) * 100 + parseInt((m[2] || '0').padEnd(2, '0'), 10) : null;
            }
            const money = (c) => '$' + Math.floor(c / 100) + '.' + pad(c % 100);

            function update() {
                const cents = toCents(priceEl.value);
                $('price-standard').textContent = cents === null ? '—' : money(cents);
                $('price-premium').textContent = cents === null ? '—' : money(cents + SURCHARGE);

                const opt = movieEl.selectedOptions[0];
                const duration = opt && opt.dataset.duration ? parseInt(opt.dataset.duration, 10) : null;
                if (!duration || !dateEl.value || !timeEl.value) {
                    $('end-title').textContent = 'ⓘ Computed end: choose a movie, date and time';
                    return;
                }
                const start = new Date(dateEl.value + 'T' + timeEl.value);
                const end = new Date(start.getTime() + (duration + TURNAROUND) * 60000);
                const day = end.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
                $('end-title').textContent = 'ⓘ Computed end: ' + day + ' at ' + pad(end.getHours()) + ':' + pad(end.getMinutes());
                $('end-detail').textContent = timeEl.value + ' + ' + duration + '-minute movie + ' + TURNAROUND + '-minute turnaround buffer. End time is calculated, not editable.';
            }

            [movieEl, dateEl, timeEl, priceEl].forEach((el) => el.addEventListener('input', update));
            update();
        })();
    </script>
@endsection