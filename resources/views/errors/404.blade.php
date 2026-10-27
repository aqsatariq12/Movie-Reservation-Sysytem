<x-errors.page code="404" icon="film"
    title="A scene we couldn’t find."
    message="This page may have moved or the link may be incomplete. Your bookings are still available from your account.">

    <x-errors.button variant="primary" :href="route('movies.index')">Browse movies</x-errors.button>
    <x-errors.button :href="route('bookings.index')">My bookings</x-errors.button>
</x-errors.page>