{{--
    Labelled text field (Figma "Labeled field"). Shows Laravel validation errors
    for the field underneath, and a blue focus outline.
    Props: name, label, type, value, autocomplete, hint (small help text shown
    inside the card, under the input). Any other attribute (required,
    autofocus, passwordrules, ...) is passed to the input.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'autocomplete' => null,
    'hint' => null,
])

@php($hasError = $errors->has($name))

<div>
    <label @class([
        'block min-h-[76px] rounded-2xl border bg-white p-[14px] shadow-[0_8px_24px_rgba(0,0,0,0.03)] transition-colors',
        'border-cb-line focus-within:border-cb-brand focus-within:ring-1 focus-within:ring-cb-brand' => ! $hasError,
        'border-red-400 ring-1 ring-red-400' => $hasError,
    ])>
        <span class="block text-[13px] uppercase leading-[1.7] text-cb-muted">{{ $label }}</span>

        <input
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $value }}"
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            {{ $attributes->class('block w-full bg-transparent text-[14px] leading-[1.7] text-cb-ink outline-none placeholder:text-cb-faint') }}
        >

        @if ($hint)
            <span class="block text-[13px] leading-[1.7] text-cb-muted">{{ $hint }}</span>
        @endif
    </label>

    @error($name)
        <p class="mt-2 text-[13px] leading-[1.45] text-red-600">{{ $message }}</p>
    @enderror
</div>
