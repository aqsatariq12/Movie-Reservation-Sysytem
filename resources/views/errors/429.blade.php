@php
    $seconds = (int) (method_exists($exception, 'getHeaders') ? ($exception->getHeaders()['Retry-After'] ?? 60) : 60);
    $seconds = max($seconds, 1);
@endphp

<x-errors.page code="429" icon="hourglass"
    title="A little breather, then try again."
    message="You’ve made too many requests in a short time. Wait a moment before trying again. Your existing bookings won’t change.">

    <x-errors.button id="retry" disabled>
        <span id="retry-label">Try again in {{ gmdate('i:s', $seconds) }}</span>
    </x-errors.button>
    <x-errors.button :href="route('movies.index')">Back to movies</x-errors.button>

    <script>
        (() => {
            let left = {{ $seconds }};
            const btn = document.getElementById('retry');
            const label = document.getElementById('retry-label');
            const fmt = s => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
            const timer = setInterval(() => {
                left--;
                if (left <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.textContent = 'Try again';
                    btn.onclick = () => window.location.reload();
                    return;
                }
                label.textContent = 'Try again in ' + fmt(left);
            }, 1000);
        })();
    </script>
</x-errors.page>