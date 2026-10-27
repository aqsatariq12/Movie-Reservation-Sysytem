<x-errors.page code="419" icon="clock-alert"
    title="Your session took an intermission."
    message="Your session expired. Sign in again before continuing. Check your booking status before repeating a payment.">

    <x-errors.button variant="primary" :href="route('login')">Sign in again</x-errors.button>
    <x-errors.button :href="route('bookings.index')">Check my bookings</x-errors.button>
</x-errors.page>