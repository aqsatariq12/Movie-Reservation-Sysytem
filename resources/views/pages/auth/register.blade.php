<x-layouts.cinebook-auth
    :title="__('Register')"
    heading="Your seat in the story"
    description="Create your customer account. Verify your email to hold seats and check out."
>
    <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-7">
        @csrf

        <x-cinebook.field
            name="name"
            label="Full name"
            :value="old('name')"
            autocomplete="name"
            required
            autofocus
        />

        <x-cinebook.field
            name="email"
            label="Email address"
            type="email"
            :value="old('email')"
            autocomplete="email"
            required
        />

        <x-cinebook.field
            name="password"
            label="Password"
            type="password"
            autocomplete="new-password"
            hint="Use at least 8 characters, including a letter and a number."
            :passwordrules="\Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString()"
            required
        />

        <x-cinebook.field
            name="password_confirmation"
            label="Confirm password"
            type="password"
            autocomplete="new-password"
            :passwordrules="\Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString()"
            required
        />

        <x-cinebook.submit-button data-test="register-user-button">Create account →</x-cinebook.submit-button>
    </form>

    <x-cinebook.auth-footer>
        <p>By creating an account, you agree to the reservation and cancellation rules.</p>

        @if (Route::has('login'))
            <p>
                <a href="{{ route('login') }}" class="hover:text-cb-brand">Already have an account? Sign in →</a>
            </p>
        @endif
    </x-cinebook.auth-footer>
</x-layouts.cinebook-auth>
