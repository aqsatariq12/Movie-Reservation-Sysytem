@extends('layouts.admin')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Operations</p>
        <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Reservation settings</h1>
    </div>

    <div class="rounded-lg border border-[#283750] bg-[#101a2d] p-4 text-sm">
        <p class="text-[#f4f7ff]">ⓘ Changes apply only to new holds and bookings</p>
        <p class="mt-1 text-[#a0aec5]">
            Existing holds keep their expiry. Existing bookings keep their prices and cancellation policy. Every change is
            audited.
        </p>
    </div>

    @php
        $fields = [
            [
                'name' => 'hold_minutes',
                'label' => 'Hold duration · minutes',
                'step' => '1',
                'min' => 2,
                'max' => 30,
                'help' => 'Allowed range: 2–30 minutes. All selected seats are held together.',
            ],
            [
                'name' => 'max_seats',
                'label' => 'Maximum seats per booking',
                'step' => '1',
                'min' => 1,
                'max' => 10,
                'help' => 'Allowed range: 1–10 seats. Customers must select at least one seat.',
            ],
            [
                'name' => 'cancel_window_hours',
                'label' => 'Customer cancellation window · hours before show',
                'step' => '1',
                'min' => 0,
                'max' => 72,
                'help' => 'Allowed range: 0–72 hours. Only confirmed, unscanned bookings are eligible.',
            ],
            [
                'name' => 'premium_surcharge',
                'label' => 'Premium seat surcharge · USD',
                'step' => '0.01',
                'min' => 0,
                'max' => 50,
                'help' => 'Allowed range: $0.00–$50.00. Added to the showtime base price.',
            ],
        ];
    @endphp

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">

        {{-- Editable values --}}
        <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}"
            class="flex flex-col gap-5 rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
            @csrf
            @method('PUT')

            <h2 class="text-2xl font-bold text-[#f4f7ff]">Four configurable values</h2>

            @foreach ($fields as $field)
                <div>
                    <label for="{{ $field['name'] }}"
                        class="block rounded-lg border {{ $errors->has($field['name']) ? 'border-red-600' : 'border-[#283750]' }} bg-[#0c1424] p-[14px] text-[13px] text-[#a0aec5]">
                        <span class="uppercase">{{ $field['label'] }}</span>
                        <input id="{{ $field['name'] }}" type="number" name="{{ $field['name'] }}"
                            value="{{ old($field['name'], $values[$field['name']]) }}"
                            data-original="{{ $values[$field['name']] }}" step="{{ $field['step'] }}" min="{{ $field['min'] }}"
                            max="{{ $field['max'] }}" required
                            class="mt-1 block w-full bg-transparent text-base text-[#f4f7ff] outline-none">
                        <span class="mt-1 block">{{ $field['help'] }}</span>
                    </label>
                    @error($field['name'])
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="flex items-center gap-4">
                <button type="submit" id="save-settings" disabled
                    class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white enabled:hover:bg-[#4a8bff] disabled:cursor-not-allowed disabled:opacity-40">
                    Save settings
                </button>
                <button type="button" id="discard-settings" disabled
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white enabled:hover:bg-[#1e2f4d] disabled:cursor-not-allowed disabled:opacity-40">
                    Discard changes
                </button>
            </div>
        </form>

        {{-- Read-only info --}}
        <div class="flex flex-col gap-4">
            <section class="flex flex-col gap-4 rounded-2xl border border-[#283750] bg-[#101a2d] p-6 text-sm">
                <h2 class="text-2xl font-bold text-[#f4f7ff]">Fixed operating rules</h2>
                <p class="text-[#a0aec5]">Sales close · {{ $salesCutoff }} minutes before start</p>
                <p class="text-[#a0aec5]">Entry opens · 60 minutes before start</p>
                <p class="text-[#a0aec5]">Entry closes · 30 minutes after start</p>
                <p class="text-[#a0aec5]">Informational only. These rules aren’t configurable here.</p>
            </section>

            <section class="flex flex-col gap-4 rounded-2xl border border-[#283750] bg-[#101a2d] p-6 text-sm">
                <h2 class="text-2xl font-bold text-[#f4f7ff]">Payment environment</h2>
                <p class="text-[#a0aec5]">
                    Stripe TEST MODE hosted Checkout. No real-money processing and no on-site card-entry form.
                </p>
            </section>
        </div>
    </div>
    <script>
        (function () {
            const form = document.getElementById('settings-form');
            const save = document.getElementById('save-settings');
            const discard = document.getElementById('discard-settings');
            const inputs = Array.from(form.querySelectorAll('input[data-original]'));

            // "2" and "2.00" count as the same number.
            const normalize = (value) => {
                const number = parseFloat(value);
                return Number.isNaN(number) ? value.trim() : number;
            };

            const isDirty = () =>
                inputs.some((input) => normalize(input.value) !== normalize(input.dataset.original));

            const refresh = () => {
                const dirty = isDirty();
                save.disabled = !dirty;
                discard.disabled = !dirty;
            };

            form.addEventListener('input', refresh);

            discard.addEventListener('click', () => {
                inputs.forEach((input) => { input.value = input.dataset.original; });
                refresh();
            });

            // Stop a double click from sending the form twice.
            form.addEventListener('submit', () => { save.disabled = true; });

            refresh();
        })();
    </script>
@endsection