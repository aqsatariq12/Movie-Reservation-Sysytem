<x-layouts.cinebook-auth
    :title="__('Forgot Password')"
    heading="Let’s get you back in"
    description="Enter your email and we’ll send password reset instructions."
>
    {{-- Password reset status --}}
    @if (session('status'))
        <x-cinebook.notice role="status">
            {{ session('status') }}
        </x-cinebook.notice>
    @endif

    {{-- Password reset form --}}
    <form
        method="POST"
        action="{{ route('password.email') }}"
        class="flex flex-col gap-7"
    >
        @csrf

        <x-cinebook.field
            name="email"
            label="Email address"
            type="email"
            :value="old('email')"
            autocomplete="email"
            required
            autofocus
        />

        <x-cinebook.submit-button>
            Send reset link →
        </x-cinebook.submit-button>
    </form>

    {{-- Information notice --}}
    @if (!session('status'))
        <x-cinebook.notice role="status">
            <p>ⓘ Check your inbox</p>
            <p>
                If an account exists for this address, a password reset
                link is on its way. Please check your spam folder too.
            </p>
        </x-cinebook.notice>
    @endif

    {{-- Footer --}}
    <x-cinebook.auth-footer>
        <p>
            <a href="{{ route('login') }}" class="hover:text-cb-brand">
                ← Back to sign in
            </a>
        </p>

        <p class="text-[13px] leading-[1.7] text-cb-muted">
            Your existing bookings and tickets won’t be changed.
        </p>

        <p class="text-[13px] leading-[1.7] text-cb-muted">
            CineBook Downtown · Secure account access.
            Reservations use Stripe hosted Checkout in test mode.
        </p>
    </x-cinebook.auth-footer>
</x-layouts.cinebook-auth>
