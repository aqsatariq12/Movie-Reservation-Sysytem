<x-layouts::auth :title="__('Confirm password')">
    <div class="min-h-full w-full bg-cb-cream px-4 py-10 sm:px-6 lg:py-14">
        <div class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center">

            {{-- Brand heading --}}
            <div class="mb-8 text-center">
                <a
                    href="{{ route('home') }}"
                    class="inline-flex items-center gap-2 text-2xl font-extrabold tracking-tight text-cb-ink"
                >
                    <span class="flex size-10 items-center justify-center rounded-xl bg-cb-brand text-lg text-white">
                        C
                    </span>
                    cinebook
                </a>

                <p class="mt-5 text-sm font-semibold uppercase tracking-[0.18em] text-cb-brand">
                    Account security
                </p>
            </div>

            {{-- Confirmation card --}}
            <div class="rounded-2xl border border-cb-line bg-white p-6 shadow-sm sm:p-9">

                <div class="mb-7 text-center">
                    <div class="mx-auto mb-5 flex size-14 items-center justify-center rounded-2xl bg-cb-brand/10 text-cb-brand">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="size-7"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <rect x="3" y="11" width="18" height="11" rx="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold tracking-tight text-cb-ink sm:text-3xl">
                        {{ __('Confirm your password') }}
                    </h1>

                    <p class="mt-3 text-sm leading-6 text-cb-muted">
                        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                    </p>
                </div>

                {{-- Existing session status --}}
                <x-auth-session-status
                    class="mb-5 text-center"
                    :status="session('status')"
                />

                {{-- Existing password confirmation form --}}
                <form
                    method="POST"
                    action="{{ route('password.confirm.store') }}"
                    class="flex flex-col gap-6"
                >
                    @csrf

                    <flux:input
                        name="password"
                        :label="__('Password')"
                        type="password"
                        required
                        autocomplete="current-password"
                        :placeholder="__('Enter your current password')"
                        viewable
                    />

                    <flux:button
                        variant="primary"
                        type="submit"
                        class="w-full"
                        data-test="confirm-password-button"
                    >
                        {{ __('Confirm password') }}
                    </flux:button>
                </form>

                <div class="mt-6 border-t border-cb-line pt-5 text-center">
                    <a
                        href="{{ route('home') }}"
                        class="text-sm font-semibold text-cb-brand transition hover:opacity-75"
                    >
                        ← {{ __('Back to CineBook') }}
                    </a>
                </div>
            </div>

            <p class="mt-6 text-center text-xs leading-5 text-cb-muted">
                {{ __('Your security matters. Your password helps protect your CineBook account.') }}
            </p>
        </div>
    </div>
</x-layouts::auth>