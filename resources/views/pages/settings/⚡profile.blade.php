<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
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
                Manage your profile information and keep your CineBook account up to date.
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
                    <a
                        href="{{ route('profile.edit') }}"
                        wire:navigate
                        class="flex items-center gap-3 rounded-xl bg-cb-brand/10 px-4 py-3 text-sm font-semibold text-cb-brand"
                        aria-current="page"
                    >
                        <span aria-hidden="true">◎</span>
                        Profile
                    </a>

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
                    <p class="text-sm font-semibold text-cb-ink">Your movie nights, all in one place.</p>
                    <p class="mt-1 text-xs leading-5 text-cb-muted">
                        Keep your account details current for a smoother booking experience.
                    </p>
                </div>
            </aside>

            {{-- Main content --}}
            <div class="min-w-0 space-y-6">

                {{-- Profile details --}}
                <div class="rounded-2xl border border-cb-line bg-white p-5 shadow-sm sm:p-8">
                    <div class="mb-6 border-b border-cb-line pb-5">
                        <h2 class="text-xl font-bold text-cb-ink sm:text-2xl">
                            Profile details
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-cb-muted">
                            Update your personal information and the email address associated with your account.
                        </p>
                    </div>

                    <form wire:submit="updateProfileInformation" class="space-y-6">
                        <div>
                            <flux:input
                                wire:model="name"
                                :label="__('Full name')"
                                type="text"
                                required
                                autocomplete="name"
                            />
                        </div>

                        <div>
                            <flux:input
                                wire:model="email"
                                :label="__('Email address')"
                                type="email"
                                required
                                autocomplete="email"
                            />

                            @if ($this->hasUnverifiedEmail)
                                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-medium text-amber-900">
                                        Your email address is not verified.
                                    </p>

                                    <button
                                        type="button"
                                        wire:click="resendVerificationNotification"
                                        class="mt-2 text-sm font-semibold text-amber-900 underline underline-offset-2"
                                    >
                                        Resend verification email
                                    </button>

                                    @if (session('status') === 'verification-link-sent')
                                        <p class="mt-2 text-sm font-medium text-green-700">
                                            A new verification link has been sent to your email address.
                                        </p>
                                    @endif
                                </div>
                            @endif

                            <p class="mt-2 text-xs leading-5 text-cb-muted">
                                If you change your email address, you may need to verify the new address.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-cb-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-cb-muted">
                                Make sure your details are correct before saving.
                            </p>

                            <flux:button
                                variant="primary"
                                type="submit"
                                data-test="update-profile-button"
                            >
                                Save profile
                            </flux:button>
                        </div>
                    </form>
                </div>

                {{-- Security shortcut --}}
                <div class="rounded-2xl border border-cb-line bg-white p-5 shadow-sm sm:p-8">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-cb-cream text-xl text-cb-ink">
                                ♙
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-cb-ink">
                                    Password and security
                                </h2>

                                <p class="mt-2 max-w-xl text-sm leading-6 text-cb-muted">
                                    Keep your account secure by updating your password regularly.
                                </p>
                            </div>
                        </div>

                        @if (Route::has('security.edit'))
                            <a
                                href="{{ route('security.edit') }}"
                                wire:navigate
                                class="inline-flex shrink-0 items-center justify-center rounded-full bg-cb-brand px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                            >
                                Update password
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Keep the existing account deletion component --}}
                @if ($this->showDeleteUser)
                    <div class="rounded-2xl border border-cb-line bg-white p-5 shadow-sm sm:p-8">
                        <livewire:pages::settings.delete-user-form />
                    </div>
                @endif

            </div>
        </div>
    </div>
</section>

