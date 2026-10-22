<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';



    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {

    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }


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
                Manage your security preferences and keep your CineBook account protected.
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

                    {{-- Security: active page --}}
                    <a
                        href="{{ route('security.edit') }}"
                        wire:navigate
                        class="flex items-center gap-3 rounded-xl bg-cb-brand/10 px-4 py-3 text-sm font-semibold text-cb-brand"
                        aria-current="page"
                    >
                        <span aria-hidden="true">♙</span>
                        Security
                    </a>

                    {{-- Appearance --}}
                    @if (Route::has('appearance.edit'))
                        <a
                            href="{{ route('appearance.edit') }}"
                            wire:navigate
                            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-cb-muted transition hover:bg-cb-cream hover:text-cb-ink"
                        >
                            <span aria-hidden="true">◐</span>
                            Appearance
                        </a>
                    @endif

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
                        Your account, your security.
                    </p>

                    <p class="mt-1 text-xs leading-5 text-cb-muted">
                        Use a strong, unique password to help protect your CineBook account.
                    </p>
                </div>
            </aside>

            {{-- Main content --}}
            <div class="min-w-0 space-y-6">

                {{-- Update password card --}}
                <div class="rounded-2xl border border-cb-line bg-white p-5 shadow-sm sm:p-8">

                    <div class="mb-6 border-b border-cb-line pb-5">
                        <div class="flex items-start gap-4">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-cb-cream text-xl text-cb-ink">
                                ♙
                            </div>

                            <div>
                                <h2 class="text-xl font-bold text-cb-ink sm:text-2xl">
                                    Password and security
                                </h2>

                                <p class="mt-2 text-sm leading-6 text-cb-muted">
                                    Update your password to keep your account safe. Choose a strong password that you do not use elsewhere.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" wire:submit="updatePassword" class="space-y-6">

                        {{-- Current password --}}
                        <div>
                            <flux:input
                                wire:model="current_password"
                                :label="__('Current password')"
                                type="password"
                                required
                                autocomplete="current-password"
                                viewable
                            />
                        </div>

                        {{-- New password --}}
                        <div>
                            <flux:input
                                wire:model="password"
                                :label="__('New password')"
                                type="password"
                                required
                                autocomplete="new-password"
                                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                                viewable
                            />

                            <p class="mt-2 text-xs leading-5 text-cb-muted">
                                Choose a long, unique password that is difficult to guess.
                            </p>
                        </div>

                        {{-- Confirm password --}}
                        <div>
                            <flux:input
                                wire:model="password_confirmation"
                                :label="__('Confirm new password')"
                                type="password"
                                required
                                autocomplete="new-password"
                                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                                viewable
                            />
                        </div>

                        {{-- Save button --}}
                        <div class="flex flex-col gap-3 border-t border-cb-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs leading-5 text-cb-muted">
                                Make sure your new password is secure before saving.
                            </p>

                            <flux:button
                                variant="primary"
                                type="submit"
                                data-test="update-password-button"
                            >
                                Save password
                            </flux:button>
                        </div>
                    </form>
                </div>

                {{-- Security information card --}}
                <div class="rounded-2xl border border-cb-line bg-white p-5 shadow-sm sm:p-8">
                    <div class="flex items-start gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-cb-cream text-xl text-cb-ink">
                            ✓
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-cb-ink">
                                Keep your account protected
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-cb-muted">
                                Never share your password with anyone. For better security, use a unique password for your CineBook account.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>