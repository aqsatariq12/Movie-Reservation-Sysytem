<x-errors.page code="403" icon="circle-x"
    title="This area isn’t yours."
    message="You don’t have permission to view this page. Sign in with the right account or return to movies.">

    <x-errors.button variant="primary" :href="route('movies.index')">Back to movies</x-errors.button>

    @auth
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-errors.button type="submit">Switch account</x-errors.button>
        </form>
    @else
        <x-errors.button :href="route('login')">Switch account</x-errors.button>
    @endauth
</x-errors.page>