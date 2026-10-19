<x-layouts.cinebook-auth
    :title="__('Reset Password')"
    heading="Create a new password"
    description="Choose a strong password to keep your CineBook account secure."
>
    {{-- Password reset status --}}
    @if (session('status'))
        <x-cinebook.notice role="status">
            {{ session('status') }}
        </x-cinebook.notice>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
        <x-cinebook.notice role="alert">
            <p class="font-medium">Please check the following:</p>

            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-cinebook.notice>
    @endif

    {{-- Reset password form --}}
    <form
        method="POST"
        action="{{ route('password.update') }}"
        class="flex flex-col gap-6"
    >
        @csrf

        {{-- Reset token --}}
        <input
            type="hidden"
            name="token"
            value="{{ request()->route('token') }}"
        >

        {{-- Email address --}}
        <x-cinebook.field
            name="email"
            label="Email address"
            type="email"
            :value="old('email', request('email'))"
            autocomplete="email"
            required
            autofocus
        />

        {{-- New password --}}
        <x-cinebook.field
            name="password"
            label="New password"
            type="password"
            autocomplete="new-password"
            required
        />

        {{-- Confirm password --}}
        <x-cinebook.field
            name="password_confirmation"
            label="Confirm new password"
            type="password"
            autocomplete="new-password"
            required
        />

        {{-- Submit button --}}
        <x-cinebook.submit-button>
            Reset password →
        </x-cinebook.submit-button>
    </form>

    {{-- Footer --}}
    <x-cinebook.auth-footer>
        <p>
            <a
                href="{{ route('login') }}"
                class="font-medium text-cb-brand hover:underline"
            >
                ← Back to sign in
            </a>
        </p>

        <p class="text-[13px] leading-[1.7] text-cb-muted">
            Your existing bookings and tickets won't be changed.
        </p>

        <p class="text-[13px] leading-[1.7] text-cb-muted">
            CineBook Downtown · Secure account access.
        </p>
    </x-cinebook.auth-footer>
</x-layouts.cinebook-auth>