<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="min-h-screen w-full bg-cb-cream">
    @include('partials.settings-heading')

    <div class="mx-auto max-w-[1200px] px-4 py-8 sm:px-6 lg:px-8 lg:py-12">

        {{-- Page heading --}}
        <div class="mb-8">
            <p class="mb-2 text-sm font-semibold uppercase tracking-[0.18em] text-cb-brand">
                Account settings
            </p>

            <h1 class="text-3xl font-bold tracking-tight text-cb-ink sm:text-4xl">
                My account
            </h1>

            <p class="mt-2 max-w-2xl text-sm leading-6 text-cb-muted sm:text-base">
                Personalize your CineBook experience to match your preferences.
            </p>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:gap-8">

            {{-- Left account sidebar --}}
            <aside class="rounded-2xl border border-cb-line bg-white p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-full bg-cb-brand/10 text-xl font-bold text-cb-brand">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>

                    <div class="min-w-0">
                        <h2 class="truncate text-base font-bold text-cb-ink">
                            {{ Auth::user()->name }}
                        </h2>

                        <p class="mt-1 break-all text-sm text-cb-muted">
                            {{ Auth::user()->email }}
                        </p>
                    </div>
                </div>

                <div class="my-6 border-t border-cb-line"></div>

                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-cb-muted">
                    Account
                </p>

                <nav class="space-y-2" aria-label="Account settings">

                    {{-- Profile --}}
                    <a
                        href="{{ route('profile.edit') }}"
                        wire:navigate
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-cb-muted transition hover:bg-cb-cream hover:text-cb-ink"
                    >
                        <span aria-hidden="true">◎</span>
                        Profile
                    </a>

                    {{-- Security --}}
                    @if (Route::has('security.edit'))
                        <a
                            href="{{ route('security.edit') }}"
                            wire:navigate
                            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-cb-muted transition hover:bg-cb-cream hover:text-cb-ink"
                        >
                            <span aria-hidden="true">♙</span>
                            Security
                        </a>
                    @endif

                    {{-- Appearance: active page --}}
                    <a
                        href="{{ route('appearance.edit') }}"
                        wire:navigate
                        class="flex items-center gap-3 rounded-xl bg-cb-brand/10 px-4 py-3 text-sm font-semibold text-cb-brand"
                        aria-current="page"
                    >
                        <span aria-hidden="true">◐</span>
                        Appearance
                    </a>

                    {{-- My bookings --}}
                    @if (Route::has('bookings.index'))
                        <a
                            href="{{ route('bookings.index') }}"
                            wire:navigate
                            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-cb-muted transition hover:bg-cb-cream hover:text-cb-ink"
                        >
                            <span aria-hidden="true">▤</span>
                            My bookings
                        </a>
                    @endif
                </nav>

                <div class="mt-6 rounded-xl bg-cb-cream p-4">
                    <p class="text-sm font-semibold text-cb-ink">
                        Make CineBook feel like yours.
                    </p>

                    <p class="mt-1 text-xs leading-5 text-cb-muted">
                        Choose the appearance that feels most comfortable for your movie-booking experience.
                    </p>
                </div>
            </aside>

            {{-- Main content --}}
            <div class="min-w-0 space-y-6">

                {{-- Appearance settings card --}}
                <div class="rounded-2xl border border-cb-line bg-white p-5 shadow-sm sm:p-8">

                    <div class="mb-6 border-b border-cb-line pb-5">
                        <div class="flex items-start gap-4">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-cb-cream text-xl text-cb-ink">
                                ◐
                            </div>

                            <div>
                                <h2 class="text-xl font-bold text-cb-ink sm:text-2xl">
                                    Appearance
                                </h2>

                                <p class="mt-2 text-sm leading-6 text-cb-muted">
                                    Customize how CineBook looks on your device. Choose a light or dark theme, or follow your system settings automatically.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Keep the existing Flux appearance functionality --}}
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-cb-ink">
                            Choose your theme
                        </h3>

                        <flux:radio.group
                            x-data
                            variant="segmented"
                            x-model="$flux.appearance"
                        >
                            <flux:radio value="light" icon="sun">
                                {{ __('Light') }}
                            </flux:radio>

                            <flux:radio value="dark" icon="moon">
                                {{ __('Dark') }}
                            </flux:radio>

                            <flux:radio value="system" icon="computer-desktop">
                                {{ __('System') }}
                            </flux:radio>
                        </flux:radio.group>

                        <p class="text-xs leading-5 text-cb-muted">
                            System mode automatically follows your device's appearance preference.
                        </p>
                    </div>
                </div>

                {{-- Appearance information card --}}
                <div class="rounded-2xl border border-cb-line bg-white p-5 shadow-sm sm:p-8">
                    <div class="flex items-start gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-cb-cream text-xl text-cb-ink">
                            ✓
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-cb-ink">
                                Your preferred experience
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-cb-muted">
                                Select the theme that works best for you. Your appearance preference continues to be managed by CineBook's existing Flux appearance settings.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>