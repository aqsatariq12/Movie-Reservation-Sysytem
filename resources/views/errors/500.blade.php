<x-errors.page code="500" icon="circle-alert"
    title="Something went off-script."
    message="We couldn’t complete this request. Please try again shortly. If you were paying, check My bookings before paying again.">

    <x-errors.button variant="primary" onclick="history.back()">Try again</x-errors.button>
    <x-errors.button :href="route('bookings.index')">My bookings</x-errors.button>
</x-errors.page>