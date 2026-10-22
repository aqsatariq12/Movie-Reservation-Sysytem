@extends('layouts.admin')

@section('content')
    <div class="flex items-center gap-6">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / Programming</p>
            <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Your auditoriums</h1>
        </div>
        <a href="{{ route('admin.screens.create') }}"
            class="shrink-0 rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">
            + Create screen
        </a>
    </div>

    <p class="text-base leading-[1.45] text-[#a0aec5]">
        {{ $screens->count() }} {{ \Illuminate\Support\Str::plural('screen', $screens->count()) }} at CineBook Downtown.
        Manage layout, Premium rows and disabled seats.
    </p>

    @if (session('status'))
        <div class="rounded-lg border border-green-700 bg-green-900/40 p-4 text-sm text-green-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-700 bg-red-900/40 p-4 text-sm text-red-200">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        @forelse ($screens as $screen)
            @php
                $premiumRows = $screen->premium_seats_count > 0 ? $screen->premiumRowLabels() : [];
                $bookable = $screen->seats_count - $screen->disabled_seats_count;
            @endphp

            <div class="flex flex-col gap-5 rounded-[14px] border border-[#283750] bg-[#101a2d] p-6">
                <div class="flex flex-col gap-[22px] text-sm leading-[1.6] text-[#a0aec5]">
                    <p>• {{ $screen->seats_count }} SEATS</p>

                    <p class="text-[34px] font-bold leading-[1.2] text-[#f4f7ff]">{{ $screen->name }}</p>

                    <p>Seat grid &nbsp;·&nbsp; {{ $screen->rowRange() }} · {{ $screen->total_rows }} ×
                        {{ $screen->seats_per_row }}
                    </p>
                    <p>Total seats &nbsp;·&nbsp; {{ $screen->seats_count }}</p>
                    <p>Disabled / bookable &nbsp;·&nbsp; {{ $screen->disabled_seats_count }} / {{ $bookable }}</p>
                    <p>
                        Premium rows &nbsp;·&nbsp;
                        @if ($premiumRows)
                            {{ implode(', ', $premiumRows) }} · {{ $screen->premium_seats_count }} seats
                        @else
                            None
                        @endif
                    </p>

                    @if ($screen->showtimes_count > 0)
                        <p>◷ Grid dimensions locked · Show history</p>
                    @endif
                </div>

                <div class="mt-auto flex items-center gap-4">
                    <a href="{{ route('admin.screens.show', $screen) }}"
                        class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:bg-[#1e2f4d]">
                        Manage seats →
                    </a>
                </div>
            </div>
        @empty
            <p class="text-[#a0aec5]">No screens yet. Create your first one.</p>
        @endforelse
    </div>

    <div class="rounded-lg bg-[#172f58] p-4 text-sm leading-[1.6] text-[#f4f7ff]">
        <p>ⓘ Safe seat management</p>
        <p>You cannot disable a seat with an active hold or a future booking. Grid dimensions cannot change once a
            screen has show history.</p>
    </div>
@endsection