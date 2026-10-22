@extends('layouts.admin')

@section('content')
    @php
        $editing   = $movie->exists;
        $field     = 'flex min-h-[76px] flex-col rounded-xl border border-[#283750] bg-[#080e1b] p-3.5';
        $label     = 'text-[13px] uppercase leading-[1.7] text-[#a0aec5]';
        $input     = 'w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]/60';
        $btnPrimary = 'rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white whitespace-nowrap';
        $btnGhost   = 'rounded-lg border border-[#283750] bg-[#0c1424] px-5 py-[13px] text-sm text-[#f4f7ff] whitespace-nowrap hover:border-[#347bff]';
        $selectedGenres = collect(old('genre_ids', $movie->genres->pluck('id')->all()))->map(fn ($id) => (int) $id);
        $currentStatus  = old('status', $movie->status?->value);
        $statusLabel = fn ($v) => ucfirst(str_replace('_', ' ', $v));
    @endphp

    {{-- Heading --}}
    <div class="flex items-center gap-6">
        <div class="flex-1">
            <p class="text-xs font-semibold uppercase text-[#347bff]">Cinebook Downtown / Programming / Movies</p>
            <h1 class="text-[32px] font-bold leading-normal text-[#f4f7ff]">{{ $editing ? 'Edit movie' : 'Add movie' }}</h1>
        </div>
        <button type="submit" form="movie-form" class="{{ $btnPrimary }}">{{ $editing ? 'Save changes' : 'Add movie' }}</button>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-[1fr_300px]">

        {{-- LEFT: form --}}
        <div class="flex min-w-0 flex-col gap-4">
            <form id="movie-form" method="POST" enctype="multipart/form-data"
                  action="{{ $editing ? route('admin.movies.update', $movie) : route('admin.movies.store') }}"
                  class="flex flex-col gap-5 rounded-3xl border border-[#283750] bg-[#0c1424] p-6">
                @csrf
                @if ($editing) @method('PUT') @endif

                <h2 class="text-[22px] font-bold leading-[1.13] text-[#f4f7ff]">Movie information</h2>

                {{-- Title --}}
                <div>
                    <label class="{{ $field }}">
                        <span class="{{ $label }}">Title *</span>
                        <input id="title" type="text" name="title" value="{{ old('title', $movie->title) }}" required class="{{ $input }}">
                    </label>
                    @error('title') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Slug --}}
                <div>
                    <label class="{{ $field }}">
                        <span class="{{ $label }}">Slug *</span>
                        <input id="slug" type="text" name="slug" value="{{ old('slug', $movie->slug) }}" required class="{{ $input }}">
                        <span class="text-[13px] leading-[1.7] text-[#a0aec5]">Unique URL: <span id="slug-url">/movies/{{ old('slug', $movie->slug) }}</span></span>
                    </label>
                    @error('slug') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Synopsis --}}
                <div>
                    <label class="{{ $field }} min-h-[132px]">
                        <span class="{{ $label }}">Synopsis *</span>
                        <textarea name="synopsis" rows="4" required class="{{ $input }} resize-y leading-[1.7]">{{ old('synopsis', $movie->synopsis) }}</textarea>
                    </label>
                    @error('synopsis') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Duration + Rating --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $field }}">
                            <span class="{{ $label }}">Duration (minutes) *</span>
                            <input type="number" min="1" name="duration_minutes" value="{{ old('duration_minutes', $movie->duration_minutes) }}" required class="{{ $input }}">
                        </label>
                        @error('duration_minutes') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $field }}">
                            <span class="{{ $label }}">Rating *</span>
                            <select name="rating" required class="{{ $input }}">
                                <option value="" class="bg-[#0c1424]">Select rating</option>
                                @foreach ($ratings as $rating)
                                    <option value="{{ $rating }}" class="bg-[#0c1424]" @selected(old('rating', $movie->rating) === $rating)>{{ $rating }}</option>
                                @endforeach
                            </select>
                        </label>
                        @error('rating') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Release date + Status --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $field }}">
                            <span class="{{ $label }}">Release date *</span>
                            <input type="date" name="release_date" required
                                   value="{{ old('release_date', $movie->release_date?->format('Y-m-d')) }}"
                                   class="{{ $input }} [color-scheme:dark]">
                        </label>
                        @error('release_date') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $field }}">
                            <span class="{{ $label }}">Status *</span>
                            <select name="status" required class="{{ $input }}">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" class="bg-[#0c1424]" @selected($currentStatus === $status->value)>
                                        {{ $statusLabel($status->value) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        @error('status') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Trailer --}}
                <div>
                    <label class="{{ $field }}">
                        <span class="{{ $label }}">Trailer URL (HTTPS)</span>
                        <input type="url" name="trailer_url" value="{{ old('trailer_url', $movie->trailer_url) }}"
                               placeholder="https://…" class="{{ $input }}">
                    </label>
                    @error('trailer_url') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Genres --}}
                <div class="flex flex-col gap-2.5">
                    <p class="text-[13px] font-semibold leading-[1.45] text-[#f4f7ff]">Genres *</p>
                    <div class="grid grid-cols-2 gap-4 text-xs sm:grid-cols-3 lg:grid-cols-5">
                        @foreach ($genres as $genre)
                            <label class="flex cursor-pointer items-center gap-1">
                                <input type="checkbox" name="genre_ids[]" value="{{ $genre->id }}"
                                       class="peer sr-only" @checked($selectedGenres->contains($genre->id))>
                                <span class="text-[#a0aec5] peer-checked:text-[#347bff] peer-focus-visible:underline">• {{ $genre->name }}</span>
                                <span class="hidden text-[#347bff] peer-checked:inline">✓</span>
                            </label>
                        @endforeach
                    </div>
                    @error('genre_ids') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </form>

            {{-- Bottom actions --}}
            <div class="flex items-center gap-4">
                <button type="submit" form="movie-form" class="{{ $btnPrimary }}">{{ $editing ? 'Save changes' : 'Add movie' }}</button>
                <a href="{{ route('admin.movies.index') }}" class="{{ $btnGhost }}">Cancel</a>
            </div>
        </div>

        {{-- RIGHT: poster + archive --}}
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-5 rounded-3xl border border-[#283750] bg-[#0c1424] p-6">
                <h2 class="text-[22px] font-bold leading-[1.13] text-[#f4f7ff]">Poster</h2>

                <div class="h-[336px] w-[250px] max-w-full">
                    <img id="poster-preview" src="{{ $movie->poster_url }}" alt="Poster"
                         class="{{ $movie->poster_url ? '' : 'hidden' }} size-full rounded-2xl object-cover">
                    <div id="poster-placeholder"
                         class="{{ $movie->poster_url ? 'hidden' : 'flex' }} size-full items-center justify-center rounded-2xl border border-dashed border-[#283750] text-xs text-[#a0aec5]">
                        No poster yet
                    </div>
                </div>

                <label class="{{ $btnGhost }} w-fit cursor-pointer">
                    <span id="poster-btn-text">{{ $movie->poster_path ? 'Replace poster' : 'Upload poster' }}</span>
                    <input id="poster" type="file" name="poster" form="movie-form" accept=".jpg,.jpeg,.png,.webp" class="sr-only">
                </label>

                <p id="poster-meta" class="text-xs leading-[1.45] text-[#a0aec5]">
                    @if ($movie->poster_path) {{ basename($movie->poster_path) }} · @endif
                    JPG, PNG or WebP · Maximum 2 MB
                </p>
                @error('poster') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            @if ($editing && $hasShowtimes)
                <div class="rounded-xl border border-[#283750] bg-[#080e1b] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
                    <p>ⓘ Show history exists</p>
                    <p>This movie has showtimes. Archive it instead of deleting it.</p>
                </div>
            @endif

            @if ($editing && $movie->status?->value !== 'archived')
                <form method="POST" action="{{ route('admin.movies.archive', $movie) }}"
                      onsubmit="return confirm('Archive this movie? It will disappear from public discovery.')">
                    @csrf @method('PATCH')
                    <button type="submit" class="{{ $btnGhost }}">Archive movie</button>
                </form>
            @endif
        </div>
    </div>

    <script>
        (function () {
            const title = document.getElementById('title');
            const slug = document.getElementById('slug');
            const slugUrl = document.getElementById('slug-url');
            const poster = document.getElementById('poster');
            let slugTouched = {{ $editing ? 'true' : 'false' }}; // don't rewrite slugs of existing movies

            const slugify = s => s.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            const syncUrl = () => slugUrl.textContent = '/movies/' + (slug.value || '…');

            title.addEventListener('input', () => { if (!slugTouched) { slug.value = slugify(title.value); syncUrl(); } });
            slug.addEventListener('input', () => { slugTouched = true; syncUrl(); });

            poster.addEventListener('change', e => {
                const file = e.target.files[0];
                if (!file) return;
                const img = document.getElementById('poster-preview');
                img.src = URL.createObjectURL(file);
                img.classList.remove('hidden');
                document.getElementById('poster-placeholder').classList.add('hidden');
                document.getElementById('poster-btn-text').textContent = 'Replace poster';
                document.getElementById('poster-meta').textContent =
                    file.name + ' · ' + (file.size / 1048576).toFixed(1) + ' MB · JPG, PNG or WebP · Maximum 2 MB';
            });
        })();
    </script>
@endsection