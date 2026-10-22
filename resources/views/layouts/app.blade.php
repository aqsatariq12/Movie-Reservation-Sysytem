<x-layouts.cinebook :title="$title ?? 'CineBook Settings'">
    <flux:main>
        {{ $slot }}
    </flux:main>

    @fluxScripts
</x-layouts.cinebook>
