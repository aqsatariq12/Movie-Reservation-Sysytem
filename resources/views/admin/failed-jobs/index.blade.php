@extends('layouts.admin')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Operations</p>
        <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Failed jobs</h1>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
        @if ($jobs->total() > 0)
            <p class="text-xs text-red-300">! {{ $jobs->total() }} need review</p>
        @else
            <p class="text-xs text-[#a0aec5]">Nothing needs review</p>
        @endif
        <p class="text-[#a0aec5]">Background tasks only · Current booking states remain authoritative.</p>
    </div>

    <form method="GET" action="{{ route('admin.failed-jobs.index') }}" class="grid gap-6 md:grid-cols-3">
        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Job class</span>
            <select name="job_class" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                <option value="">All job classes</option>
                @foreach ($jobClasses as $name)
                    <option value="{{ $name }}" @selected($jobClass === $name)>{{ class_basename($name) }}</option>
                @endforeach
            </select>
        </label>

        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Time range</span>
            <select name="range" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                @foreach ($ranges as $key => $label)
                    <option value="{{ $key }}" @selected($range === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Search summary</span>
            <input type="search" name="search" value="{{ $search }}" placeholder="Search reference or message…"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]">
        </label>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
        <table class="w-full min-w-[820px] text-left text-[13px] text-[#f4f7ff]">
            <thead class="text-[#a0aec5]">
                <tr class="uppercase">
                    <th class="pb-5 font-normal">Failed at</th>
                    <th class="pb-5 font-normal">Job class</th>
                    <th class="pb-5 font-normal">Summary</th>
                    <th class="pb-5 font-normal">Exception</th>
                    <th class="pb-5 font-normal text-[#87beff]">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr class="border-t border-[#283750]">
                        <td class="py-4">{{ $job['failed_at']->format('M j · H:i') }}</td>
                        <td class="py-4 text-[#a0aec5]">{{ $job['class'] }}</td>
                        <td class="py-4 text-[#a0aec5]">{{ $job['summary'] }}</td>
                        <td class="py-4 text-[#a0aec5]">{{ $job['exception_class'] }}</td>
                        <td class="py-4">
                            <a href="{{ route('admin.failed-jobs.show', $job['uuid']) }}"
                                class="text-[#87beff] hover:underline">Details →</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-[#a0aec5]">No failed jobs in this view.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between gap-4 text-sm">
        <p class="text-xs text-[#a0aec5]">
            Showing {{ $jobs->firstItem() ?? 0 }}–{{ $jobs->lastItem() ?? 0 }} of {{ $jobs->total() }} failed jobs
        </p>

        <div class="flex items-center gap-4">
            @if ($jobs->previousPageUrl())
                <a href="{{ $jobs->previousPageUrl() }}"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">← Previous</a>
            @else
                <span class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b]/50 px-5 py-[13px] text-white/40">← Previous</span>
            @endif

            <span class="text-xs text-[#87beff]">• {{ $jobs->currentPage() }}</span>

            @if ($jobs->nextPageUrl())
                <a href="{{ $jobs->nextPageUrl() }}"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">Next →</a>
            @else
                <span class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b]/50 px-5 py-[13px] text-white/40">Next →</span>
            @endif
        </div>
    </div>

    <div class="rounded-lg border border-red-900/60 bg-red-950/30 p-4 text-sm">
        <p class="font-medium text-[#f4f7ff]">ⓘ Email failure never changes a booking</p>
        <p class="mt-1 text-[#a0aec5]">
            @if ($notice && $notice['confirmed'])
                {{ $notice['reference'] }} is still confirmed. Its tickets remain valid, even though the confirmation email needs another delivery attempt.
            @elseif ($notice)
                {{ $notice['reference'] }} is currently {{ $notice['status'] }}. The failed email did not change it.
            @else
                A failed email or background task never changes a booking. Bookings, payments and refunds keep their own status.
            @endif
        </p>
    </div>
@endsection