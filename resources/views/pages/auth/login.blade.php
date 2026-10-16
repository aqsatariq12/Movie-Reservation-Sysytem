@php
    // Laravel stores the page a guest tried to open in "url.intended".
    // Once the seat-selection pages exist, a visitor sent here from a showtime
    // gets the "your showtime is waiting" notice from the design.
    $intended = session('url.intended');
    $returningToShowtime = is_string($intended) && str_contains($intended, 'showtime');
@endphp

<x-layouts.cinebook-auth
    :title="__('Log in')"
    heading="Welcome back"
    description="Sign in to reserve your next movie night."
>
    @if (session('status'))
        <x-cinebook.notice role="status">{{ session('status') }}</x-cinebook.notice>
    @endif

    @if ($returningToShowtime)
        <x-cinebook.notice>
            <p>ⓘ Your showtime is waiting</p>
            <p>After sign-in, return to seat selection. Seats aren’t held yet.</p>
        </x-cinebook.notice>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-7">
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

        <x-cinebook.field
            name="password"
            label="Password"
            type="password"
            autocomplete="current-password"
            required
        />

        <div class="flex items-center gap-4 text-[13px] leading-[1.45]">
            <p class="flex-1 text-cb-muted">Secure email + password access</p>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-cb-brand hover:underline">Forgot password?</a>
            @endif
        </div>

        <x-cinebook.submit-button data-test="login-button">
            {{ $returningToShowtime ? 'Sign in & return to showtime →' : 'Sign in →' }}
        </x-cinebook.submit-button>
    </form>

    <div class="text-[14px] leading-[1.6] text-cb-muted">
        @if (Route::has('register'))
            <p>
                <a href="{{ route('register') }}" class="hover:text-cb-brand">New to CineBook? Create a customer account →</a>
            </p>
        @endif

        <p class="mt-[22px]">CineBook Downtown · Secure account access. Reservations use Stripe hosted Checkout in test mode.</p>
    </div>
</x-layouts.cinebook-auth>
