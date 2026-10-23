@extends('layouts.admin')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">
            Cinebook Downtown / Operations /
            <a href="{{ route('admin.failed-jobs.index') }}" class="hover:underline">Failed jobs</a>
        </p>
        <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">{{ $job['title'] }}</h1>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-700 bg-red-900/40 p-4 text-sm text-red-200">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="rounded-lg border p-4 text-sm {{ $notice['tone'] === 'info' ? 'border-[#347bff]/30 bg-[#347bff]/10' : 'border-amber-700/50 bg-amber-900/20' }}">
        <p class="text-[#f4f7ff]">{{ $notice['title'] }}</p>
        <p class="mt-1 text-[#a0aec5]">{{ $notice['body'] }}</p>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">

        {{-- Job details --}}
        <section class="flex flex-col gap-4 rounded-2xl border border-[#283750] bg-[#101a2d] p-6 text-sm">
            <h2 class="text-2xl font-bold text-[#f4f7ff]">{{ $job['class'] }}</h2>

            <p class="text-[#a0aec5]">Failed at · {{ $job['failed_at']->format('M j, Y · H:i:s') }}</p>
            @if ($job['reference'])
                <p class="text-[#a0aec5]">Related booking · {{ $job['reference'] }}</p>
            @endif
            <p class="text-[#a0aec5]">Attempts · {{ $job['attempts'] ?? '—' }}</p>
            <p class="text-[#a0aec5]">Queue · {{ $job['queue'] }}</p>

            <div>
                <p class="text-[#a0aec5]">Sanitized exception</p>
                <p class="mt-3 leading-relaxed text-[#a0aec5]">
                    {{ $job['exception_class'] }}
                    {{ $job['message'] }}
                    @if ($job['reference']) Context: {{ $job['reference'] }} @endif
                    No credentials, tokens, personal payloads or internal stack paths are displayed.
                </p>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <a href="{{ route('admin.failed-jobs.show', [$job['uuid'], 'action' => 'retry']) }}#retry-panel"
                    class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">Retry job…</a>
                <a href="{{ route('admin.failed-jobs.show', [$job['uuid'], 'action' => 'delete']) }}#delete-panel"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:bg-[#1e2f4d]">Delete failed job…</a>
            </div>
        </section>

        {{-- Confirmations --}}
        <div class="flex flex-col gap-4">
            @if ($action === 'retry')
                <section id="retry-panel" class="flex flex-col gap-4 rounded-2xl border border-[#283750] bg-[#101a2d] p-6 text-sm">
                    <p class="text-xs uppercase text-[#a0aec5]">• Retry confirmation</p>
                    <h2 class="text-2xl font-bold text-[#f4f7ff]">Try delivery again?</h2>
                    <p class="text-[#a0aec5]">
                        Queue one retry of this existing job.
                        @if ($notice['tone'] === 'info') The reservation remains confirmed either way. @endif
                    </p>
                    <form method="POST" action="{{ route('admin.failed-jobs.retry', $job['uuid']) }}" class="flex items-center gap-4">
                        @csrf
                        <a href="{{ route('admin.failed-jobs.show', $job['uuid']) }}"
                            class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">Not now</a>
                        <button type="submit"
                            class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-white hover:bg-[#4a8bff]">Confirm retry</button>
                    </form>
                </section>
            @endif

            @if ($action === 'delete')
                <section id="delete-panel" class="flex flex-col gap-4 rounded-2xl border border-red-900/60 bg-red-950/30 p-6 text-sm">
                    <p class="text-xs uppercase text-red-300">! Delete confirmation</p>
                    <h2 class="text-2xl font-bold text-[#f4f7ff]">Remove this failed job?</h2>
                    <p class="text-[#a0aec5]">
                        This removes the failed queue record, not the booking. No email is sent. This cannot be undone.
                    </p>
                    <form method="POST" action="{{ route('admin.failed-jobs.destroy', $job['uuid']) }}" class="flex items-center gap-4">
                        @csrf
                        @method('DELETE')
                        <a href="{{ route('admin.failed-jobs.show', $job['uuid']) }}"
                            class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">Keep job</a>
                        <button type="submit"
                            class="rounded-lg border border-red-700 bg-red-900/40 px-5 py-[13px] text-red-200 hover:bg-red-900/60">Confirm delete</button>
                    </form>
                </section>
            @endif
        </div>
    </div>
@endsection