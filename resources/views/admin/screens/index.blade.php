<x-layouts::app :title="__('Screens')">
    <div class="flex flex-col gap-6 p-6">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-blue-400">Programming</p>
                <h1 class="text-3xl font-semibold">Your auditoriums</h1>
                <p class="mt-2 text-sm text-zinc-400">
                    {{ $screens->count() }} {{ \Illuminate\Support\Str::plural('screen', $screens->count()) }}.
                    Manage layout, Premium rows and disabled seats.
                </p>
            </div>
            <a href="{{ route('admin.screens.create') }}"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">
                + Create screen
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-700 bg-green-900/40 p-3 text-sm text-green-200">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-700 bg-red-900/40 p-3 text-sm text-red-200">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($screens as $screen)
                @php
                    $premiumRows = $screen->premium_seats_count > 0 ? $screen->premiumRowLabels() : [];
                    $bookable = $screen->seats_count - $screen->disabled_seats_count;
                @endphp

                <div class="flex flex-col gap-3 rounded-xl border border-zinc-700 bg-zinc-900 p-6">
                    <h2 class="text-2xl font-semibold">{{ $screen->name }}</h2>

                    <dl class="flex flex-col gap-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-400">Seat grid</dt>
                            <dd>{{ $screen->rowRange() }} · {{ $screen->total_rows }} × {{ $screen->seats_per_row }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-400">Total seats</dt>
                            <dd>{{ $screen->seats_count }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-400">Disabled / bookable</dt>
                            <dd>{{ $screen->disabled_seats_count }} / {{ $bookable }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-400">Premium rows</dt>
                            <dd>
                                @if ($premiumRows)
                                    {{ implode(', ', $premiumRows) }} · {{ $screen->premium_seats_count }} seats
                                @else
                                    None
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($screen->showtimes_count > 0)
                        <p class="text-xs text-zinc-400">Grid dimensions locked · {{ $screen->showtimes_count }} showtime(s)</p>
                    @endif

                    <div class="mt-2 flex items-center gap-3">
                        <a href="{{ route('admin.screens.show', $screen) }}"
                            class="rounded-lg border border-zinc-600 px-4 py-2 text-sm hover:bg-zinc-800">
                            Manage seats →
                        </a>
                        <a href="{{ route('admin.screens.edit', $screen) }}"
                            class="text-sm text-zinc-400 hover:underline">Rename</a>
                        <form method="POST" action="{{ route('admin.screens.destroy', $screen) }}"
                            onsubmit="return confirm('Delete this screen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-400 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-zinc-400">No screens yet. Create your first one.</p>
            @endforelse
        </div>

        <div class="rounded-lg border border-blue-900 bg-blue-950/60 p-4 text-sm">
            <p class="font-medium">Safe seat management</p>
            <p class="text-zinc-300">
                You cannot disable a seat with an active hold or a future booking.
                Grid dimensions cannot change once a screen has show history.
            </p>
        </div>
    </div>
</x-layouts::app>