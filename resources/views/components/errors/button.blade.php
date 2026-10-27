@props(['href' => null, 'variant' => 'secondary', 'type' => 'button'])

@php
    $classes = 'inline-flex items-center rounded-xl border px-5 py-[13px] text-sm transition disabled:cursor-not-allowed disabled:text-[#6b7280] '
        . ($variant === 'primary'
            ? 'border-[#347bff] bg-[#347bff] text-white hover:bg-[#2a68dc]'
            : 'border-[#e3d8c8] bg-white text-cb-ink hover:bg-cb-cream');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif