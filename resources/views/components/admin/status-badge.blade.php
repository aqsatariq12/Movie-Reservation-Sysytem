@props(['tone' => 'muted'])

@php
    $classes = match ($tone) {
        'info'    => 'bg-[#347bff]/15 text-[#87beff]',
        'warning' => 'bg-amber-500/15 text-amber-300',
        'success' => 'bg-green-500/15 text-green-300',
        'danger'  => 'bg-red-500/15 text-red-300',
        default   => 'bg-[#283750] text-[#a0aec5]',
    };
@endphp

<span {{ $attributes->class(['inline-block rounded-full px-[10px] py-[5px] text-xs font-semibold', $classes]) }}>
    {{ $slot }}
</span>