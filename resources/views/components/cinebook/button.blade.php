@props(['href' => '#', 'variant' => 'secondary'])

@php
    $base = 'inline-flex items-center whitespace-nowrap rounded-xl px-5 py-[13px] text-[14px] font-semibold leading-[1.21] transition-colors';

    $variants = [
        'primary' => 'bg-cb-brand text-white hover:bg-cb-brand/90',
        'secondary' => 'border border-cb-line bg-white text-cb-ink hover:bg-cb-cream',
    ];
@endphp

<a href="{{ $href }}" {{ $attributes->class([$base, $variants[$variant] ?? $variants['secondary']]) }}>{{ $slot }}</a>
