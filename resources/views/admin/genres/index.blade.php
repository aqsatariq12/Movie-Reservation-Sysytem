@extends('layouts.admin')

@section('content')
    @php
        use Illuminate\Support\Arr;
        use Illuminate\Support\Str;

        $field   = 'flex min-h-[76px] flex-col rounded-xl border border-[#283750] bg-[#080e1b] p-3.5';
        $label   = 'text-[13px] uppercase leading-[1.7] text-[#a0aec5]';
        $input   = 'w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]/60';
        $panel   = 'flex flex-col gap-5 rounded-2xl border border-[#283750] bg-[#0c1424] p-6';
        $btnPrimary = 'rounded-xl border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white whitespace-nowrap';
        $btnGhost   = 'rounded-xl border border-[#283750] bg-[#0c1424] px-5 py-[13px] text-sm text-[#f4f7ff] whitespace-nowrap hover:border-[#347bff]';
        $btnDanger  = 'rounded-xl border border-[#f2808c] bg-[#f2808c]/10 px-5 py-[13px] text-sm text-[#fda4af] whitespace-nowrap';

        $createFailed = $errors->hasBag('create');
        $editFailed   = $errors->hasBag('edit');
    @endphp

    {{-- Heading --}}
    <div class="flex items-center">
        <div class="flex-1">
            <p class="text-xs font-semibold uppercase text-[#347bff]">Cinebook Downtown / Programming</p>
            <h1 class="text-[32px] font-bold leading-normal text-[#f4f7ff]">Genres</h1>
        </div>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-[1fr_340px]">

        {{-- LEFT --}}
        <div class="flex min-w-0 flex-col gap-4">

            {{-- Table --}}
            <div class="overflow-x-auto rounded-2xl border border-[#283750] bg-[#0c1424] p-4">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left uppercase">
                            <th class="h-[58px] w-1/4 pr-3 font-normal text-[#f4f7ff]">Genre</th>
                            <th class="h-[58px] w-1/4 pr-3 font-normal text-[#a0aec5]">Slug</th>
                            <th class="h-[58px] w-1/4 pr-3 font-normal text-[#a0aec5]">Movies</th>
                            <th class="h-[58px] w-1/4 font-normal text-[#a0aec5]">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($genres as $genre)
                            <tr class="whitespace-nowrap">
                                <td class="h-[58px] pr-3 text-[#f4f7ff]">{{ $genre->name }}</td>
                                <td class="h-[58px] pr-3 text-[#a0aec5]">{{ $genre->slug }}</td>
                                <td class="h-[58px] pr-3 text-[#a0aec5]">{{ $genre->movies_count }}</td>
                                <td class="h-[58px] text-[#a0aec5]">
                                    <a href="{{ route('admin.genres.index', ['edit' => $genre->id]) }}" class="hover:text-white">Edit</a>
                                    /
                                    <a href="{{ route('admin.genres.index', ['delete' => $genre->id]) }}#delete-panel" class="hover:text-white">Delete</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center text-[#a0aec5]">No genres yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-[13px] leading-[1.45] text-[#a0aec5]">
                {{ $genres->count() }} {{ Str::plural('genre', $genres->count()) }} · Used to filter public movie discovery.
            </p>

            {{-- Delete confirmation --}}
            @if ($deleting)
                <div id="delete-panel" class="{{ $panel }}">
                    @if ($deleting->movies_count > 0)
                        {{-- IN USE --}}
                        <div class="text-sm leading-[1.6] text-[#a0aec5]">
                            <p>! DELETE CONFIRMATION · IN USE</p>
                            <p class="my-3 text-[22px] font-bold leading-[1.2] text-[#f4f7ff]">
                                {{ $deleting->name }} is used by {{ $deleting->movies_count }} {{ Str::plural('movie', $deleting->movies_count) }}.
                            </p>
                            <p>
                                This genre can’t be deleted while movies use it. Remove the genre from
                                {{ Arr::join($deletingTitles->all(), ', ', ' and ') }} first.
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <a href="{{ route('admin.genres.index') }}" class="{{ $btnGhost }}">Keep genre</a>
                            <button type="button" disabled title="Remove this genre from its movies first"
                                    class="{{ $btnDanger }} cursor-not-allowed opacity-50">Delete genre</button>
                        </div>
                    @else
                        {{-- UNUSED --}}
                        <div class="text-sm leading-[1.6] text-[#a0aec5]">
                            <p>Delete unused genre “{{ $deleting->name }}”?</p>
                            <p class="mt-3">For an unused genre, deletion permanently removes its name and slug. This cannot be undone.</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <a href="{{ route('admin.genres.index') }}" class="{{ $btnGhost }}">Keep genre</a>
                            <form method="POST" action="{{ route('admin.genres.destroy', $deleting) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="{{ $btnDanger }}">Confirm deletion</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- RIGHT --}}
        <div class="flex flex-col gap-4">

            {{-- Edit genre (only when ?edit=ID) --}}
            @if ($editing)
                <form method="POST" action="{{ route('admin.genres.update', $editing) }}" class="{{ $panel }}">
                    @csrf @method('PUT')
                    <h2 class="text-[22px] font-bold leading-[1.13] text-[#f4f7ff]">Edit genre</h2>

                    <div>
                        <label class="{{ $field }}">
                            <span class="{{ $label }}">Name *</span>
                            <input type="text" name="name" required class="{{ $input }}"
                                   value="{{ $editFailed ? old('name') : $editing->name }}">
                        </label>
                        @if ($editFailed && $errors->getBag('edit')->has('name'))
                            <p class="mt-1 text-xs text-red-400">{{ $errors->getBag('edit')->first('name') }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="{{ $field }}">
                            <span class="{{ $label }}">Slug *</span>
                            <input type="text" name="slug" required class="{{ $input }}"
                                   value="{{ $editFailed ? old('slug') : $editing->slug }}">
                        </label>
                        @if ($editFailed && $errors->getBag('edit')->has('slug'))
                            <p class="mt-1 text-xs text-red-400">{{ $errors->getBag('edit')->first('slug') }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit" class="{{ $btnPrimary }}">Save genre</button>
                        <a href="{{ route('admin.genres.index') }}" class="{{ $btnGhost }}">Cancel</a>
                    </div>
                </form>
            @endif

            {{-- Create genre --}}
            <form method="POST" action="{{ route('admin.genres.store') }}" class="{{ $panel }}">
                @csrf
                <h2 class="text-[22px] font-bold leading-[1.13] text-[#f4f7ff]">Create genre</h2>

                <div>
                    <label class="{{ $field }}">
                        <span class="{{ $label }}">Name *</span>
                        <input id="new-name" type="text" name="name" required placeholder="Enter genre name"
                               value="{{ $createFailed ? old('name') : '' }}" class="{{ $input }}">
                    </label>
                    @if ($createFailed && $errors->getBag('create')->has('name'))
                        <p class="mt-1 text-xs text-red-400">{{ $errors->getBag('create')->first('name') }}</p>
                    @endif
                </div>

                <div>
                    <label class="{{ $field }}">
                        <span class="{{ $label }}">Slug *</span>
                        <input id="new-slug" type="text" name="slug" placeholder="generated-from-name"
                               value="{{ $createFailed ? old('slug') : '' }}" class="{{ $input }}">
                    </label>
                    @if ($createFailed && $errors->getBag('create')->has('slug'))
                        <p class="mt-1 text-xs text-red-400">{{ $errors->getBag('create')->first('slug') }}</p>
                    @endif
                </div>

                <div><button type="submit" class="{{ $btnPrimary }}">Add genre</button></div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const name = document.getElementById('new-name');
            const slug = document.getElementById('new-slug');
            let touched = slug.value !== '';
            const slugify = s => s.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            name.addEventListener('input', () => { if (!touched) slug.value = slugify(name.value); });
            slug.addEventListener('input', () => { touched = slug.value !== ''; });
        })();
    </script>
@endsection