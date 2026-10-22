<div class="min-w-0 text-cb-ink">

    {{-- Settings navigation --}}
    <div class="mb-8 border-b border-cb-line">
        <nav class="flex flex-wrap gap-x-7 gap-y-3" aria-label="Settings">
            <a href="{{ route('profile.edit') }}"
                wire:navigate
                @class([
                    'border-b-2 pb-3 text-sm font-semibold transition',
                    'border-cb-brand text-cb-ink' => request()->routeIs('profile.edit'),
                    'border-transparent text-cb-muted hover:text-cb-ink' => !request()->routeIs('profile.edit'),
                ])>
                Profile
            </a>

            <a href="{{ route('security.edit') }}"
                wire:navigate
                @class([
                    'border-b-2 pb-3 text-sm font-semibold transition',
                    'border-cb-brand text-cb-ink' => request()->routeIs('security.edit'),
                    'border-transparent text-cb-muted hover:text-cb-ink' => !request()->routeIs('security.edit'),
                ])>
                Security
            </a>

            <a href="{{ route('appearance.edit') }}"
                wire:navigate
                @class([
                    'border-b-2 pb-3 text-sm font-semibold transition',
                    'border-cb-brand text-cb-ink' => request()->routeIs('appearance.edit'),
                    'border-transparent text-cb-muted hover:text-cb-ink' => !request()->routeIs('appearance.edit'),
                ])>
                Appearance
            </a>
        </nav>
    </div>

    {{-- Current settings page --}}
    <section class="min-w-0">
        <h2 class="text-2xl font-bold leading-tight text-cb-ink">
            {{ $heading ?? 'Settings' }}
        </h2>

        @if (!empty($subheading))
            <p class="mt-2 text-sm leading-6 text-cb-muted">
                {{ $subheading }}
            </p>
        @endif

        <div class="mt-6 w-full max-w-2xl">
            {{ $slot }}
        </div>
    </section>

</div>