@extends('layouts.admin')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Operations</p>
        <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Audit log</h1>
    </div>

    <form method="GET" action="{{ route('admin.audit.index') }}" class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Actor</span>
            <select name="actor" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                <option value="">All actors</option>
                @foreach ($actors as $actor)
                    <option value="{{ $actor->id }}" @selected((int) request('actor') === $actor->id)>{{ $actor->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Action</span>
            <select name="action" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </label>

        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Target</span>
            <input type="search" name="target" value="{{ request('target') }}" placeholder="Search target…"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]">
        </label>

        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Date range</span>
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [color-scheme:dark]">
        </label>
    </form>

    <div class="grid items-start gap-6 {{ $selected ? 'lg:grid-cols-[minmax(0,1fr)_380px]' : '' }}">

        {{-- Table --}}
        <div class="flex min-w-0 flex-col gap-4">
            <div class="overflow-x-auto rounded-2xl border border-[#283750] bg-[#101a2d] p-4">
                <table class="w-full min-w-[560px] text-left text-[13px] text-[#f4f7ff]">
                    <thead class="text-[#a0aec5]">
                        <tr class="uppercase">
                            <th class="px-3 pb-4 font-normal">Time</th>
                            <th class="px-3 pb-4 font-normal">Actor</th>
                            <th class="px-3 pb-4 font-normal">Action</th>
                            <th class="px-3 pb-4 font-normal">Target</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php
                                $link = route('admin.audit.index', [...request()->except(['view']), 'view' => $log->id]) . '#audit-drawer';
                            @endphp
                            <tr class="border-t border-[#283750] {{ $selected?->id === $log->id ? 'bg-[#17243b]' : '' }}">
                                <td class="px-3 py-4">
                                    <a href="{{ $link }}" class="hover:underline">
                                        {{ $date ? $log->created_at->format('H:i') : $log->created_at->format('M j · H:i') }}
                                    </a>
                                </td>
                                <td class="px-3 py-4 text-[#a0aec5]">{{ $log->user?->name ?? 'System' }}</td>
                                <td class="px-3 py-4 text-[#a0aec5]">
                                    <a href="{{ $link }}" class="hover:text-white hover:underline">{{ $log->action }}</a>
                                </td>
                                <td class="max-w-[220px] truncate px-3 py-4 text-[#a0aec5]">{{ $targets[$log->id] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-[#a0aec5]">No audit entries match your
                                    filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @php
                $current = $logs->currentPage();
                $last = $logs->lastPage();
                $pages = range(max(1, $current - 2), min($last, $current + 2));
            @endphp

            <div class="flex flex-wrap items-center justify-between gap-4 text-sm">
                <p class="text-xs text-[#a0aec5]">
                    Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries
                </p>

                <div class="flex items-center gap-3">
                    @if ($logs->previousPageUrl())
                        <a href="{{ $logs->previousPageUrl() }}"
                            class="rounded-lg border border-[#283750] bg-[#17243b] px-4 py-2 text-white hover:bg-[#1e2f4d]">←
                            Previous</a>
                    @else
                        <span
                            class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b]/50 px-4 py-2 text-white/40">←
                            Previous</span>
                    @endif

                    @foreach ($pages as $page)
                        @if ($page === $current)
                            <span class="px-1 text-xs font-semibold text-[#87beff]">• {{ $page }}</span>
                        @else
                            <a href="{{ $logs->url($page) }}" class="px-1 text-xs text-[#a0aec5] hover:text-white">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if ($logs->nextPageUrl())
                        <a href="{{ $logs->nextPageUrl() }}"
                            class="rounded-lg border border-[#283750] bg-[#17243b] px-4 py-2 text-white hover:bg-[#1e2f4d]">Next
                            →</a>
                    @else
                        <span
                            class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b]/50 px-4 py-2 text-white/40">Next
                            →</span>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4 text-sm">
                <p class="font-medium text-[#f4f7ff]">ⓘ Immutable operational history</p>
                <p class="mt-1 text-[#a0aec5]">
                    Read-only audit records. No edit or delete actions. Secrets, payment tokens and password data are never
                    shown.
                </p>
            </div>
        </div>

        {{-- Detail drawer --}}
        @if ($selected)
            <aside id="audit-drawer" class="flex flex-col gap-4 rounded-2xl border border-[#283750] bg-[#101a2d] p-6 text-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs uppercase text-[#87beff]">• Detail drawer</p>
                    <a href="{{ route('admin.audit.index', request()->except(['view'])) }}"
                        class="text-lg text-[#a0aec5] hover:text-white" aria-label="Close">✕</a>
                </div>

                <h2 class="text-2xl font-bold text-[#f4f7ff]">{{ $title }}</h2>

                <p class="text-[#a0aec5]">
                    Actor · {{ $selected->user ? $selected->user->name . ' · ' . $selected->user->role->label() : 'System' }}
                </p>
                <p class="text-[#a0aec5]">
                    Target ·
                    {{ ($targets[$selected->id] ?? '—') === 'Settings' ? 'Cinema settings' : ($targets[$selected->id] ?? '—') }}
                </p>
                <p class="text-[#a0aec5]">Time · {{ $selected->created_at->format('M j, Y · H:i:s') }}</p>
                <p class="text-[#a0aec5]">IP address · {{ $selected->ip_address ?? '—' }}</p>

                <p class="text-[#a0aec5]">Before / after</p>

                @if ($changes)
                    <div class="overflow-x-auto rounded-lg border border-[#283750] bg-[#0c1424] p-3">
                        <table class="w-full text-left text-[13px] text-[#f4f7ff]">
                            <thead class="text-[#a0aec5]">
                                <tr class="uppercase">
                                    <th class="pb-3 pr-3 font-normal">Field</th>
                                    <th class="pb-3 pr-3 font-normal">Before</th>
                                    <th class="pb-3 font-normal">After</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($changes as $row)
                                    <tr class="border-t border-[#283750]">
                                        <td class="break-all py-3 pr-3">{{ $row['field'] }}</td>
                                        <td class="break-all py-3 pr-3 text-[#a0aec5]">{{ $row['before'] }}</td>
                                        <td class="break-all py-3 text-[#a0aec5]">{{ $row['after'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-[#a0aec5]">No before / after values were recorded.</p>
                @endif

                @if ($selected->action === 'settings.updated' && $changes)
                    <p class="text-xs text-[#a0aec5]">
                        Only {{ collect($changes)->pluck('field')->implode(', ') }} changed. New holds/bookings only.
                    </p>
                @endif
            </aside>
        @endif
    </div>
@endsection