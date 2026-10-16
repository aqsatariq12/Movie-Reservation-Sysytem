<x-layouts::app :title="__('Screens')">
    <div class="flex flex-col gap-4 p-4">

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Screens</h1>
            <a href="{{ route('admin.screens.create') }}"
               class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                + New Screen
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-lg bg-green-100 p-3 text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-red-100 p-3 text-red-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-100 dark:bg-neutral-800">
                    <tr>
                        <th class="p-3">Name</th>
                        <th class="p-3">Rows</th>
                        <th class="p-3">Seats / row</th>
                        <th class="p-3">Total seats</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($screens as $screen)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="p-3">{{ $screen->name }}</td>
                            <td class="p-3">{{ $screen->total_rows }}</td>
                            <td class="p-3">{{ $screen->seats_per_row }}</td>
                            <td class="p-3">{{ $screen->seats_count }}</td>
                            <td class="flex gap-3 p-3">
                                <a href="{{ route('admin.screens.show', $screen) }}" class="text-blue-600 hover:underline">View</a>
                                <a href="{{ route('admin.screens.edit', $screen) }}" class="text-yellow-600 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('admin.screens.destroy', $screen) }}"
                                      onsubmit="return confirm('Delete this screen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-neutral-500">No screens yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>