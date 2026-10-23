@extends('layouts.admin')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / People</p>
        <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Staff &amp; admins</h1>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-700 bg-red-900/40 p-4 text-sm text-red-200">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('admin.staff.index') }}">
        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Search name or email</span>
            <input type="search" name="search" value="{{ $search }}" placeholder="Search team members…"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]">
        </label>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
        <table class="w-full min-w-[760px] text-left text-[13px] text-[#f4f7ff]">
            <thead class="text-[#a0aec5]">
                <tr class="uppercase">
                    <th class="pb-5 font-normal">Name</th>
                    <th class="pb-5 font-normal">Email</th>
                    <th class="pb-5 font-normal">Role</th>
                    <th class="pb-5 font-normal">Protection</th>
                    <th class="pb-5 font-normal text-[#87beff]">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $isSelf = $user->is($actor);
                        $isLastAdmin = $lastAdmin && $user->is($lastAdmin);
                        $locked = $isSelf || $isLastAdmin;
                        $protection = match (true) {
                            $isSelf => 'You · self-change blocked',
                            $isLastAdmin => 'Last admin · demotion blocked',
                            default => match ($user->role) {
                                \App\Enums\Role::Admin => 'Full access',
                                \App\Enums\Role::Staff => 'Admission desk',
                                \App\Enums\Role::Customer => 'No team access',
                            },
                        };
                    @endphp
                    <tr class="border-t border-[#283750]">
                        <td class="py-4">{{ $user->name }}</td>
                        <td class="py-4 text-[#a0aec5]">{{ $user->email }}</td>
                        <td class="py-4 text-[#a0aec5]">{{ $user->role->label() }}</td>
                        <td class="py-4 text-[#a0aec5]">{{ $protection }}</td>
                        <td class="py-4">
                            @if ($locked)
                                <span class="text-[#a0aec5]">Read-only</span>
                            @else
                                <a href="{{ route('admin.staff.index', [...request()->only(['search', 'page']), 'change' => $user->id]) }}#role-panel"
                                    class="text-[#87beff] hover:underline">Change role →</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-[#a0aec5]">No team members match your search.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($users->hasPages())
            <div class="mt-4 flex items-center justify-between border-t border-[#283750] pt-4 text-sm">
                <p class="text-xs text-[#a0aec5]">Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}</p>
                <div class="flex gap-3">
                    @if ($users->previousPageUrl())
                        <a href="{{ $users->previousPageUrl() }}" class="rounded-lg border border-[#283750] bg-[#17243b] px-4 py-2 text-white hover:bg-[#1e2f4d]">← Previous</a>
                    @endif
                    @if ($users->nextPageUrl())
                        <a href="{{ $users->nextPageUrl() }}" class="rounded-lg border border-[#283750] bg-[#17243b] px-4 py-2 text-white hover:bg-[#1e2f4d]">Next →</a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-2">

        {{-- Confirmation panel --}}
        @if ($candidate)
            <section id="role-panel" class="flex flex-col gap-5 rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
                <p class="text-xs uppercase text-[#a0aec5]">◷ Change role · confirmation</p>
                <h2 class="text-2xl font-bold text-[#f4f7ff]">{{ $copy['title'] }}</h2>

                {{-- Picking a role reloads the panel so the wording updates. --}}
                <form method="GET" action="{{ route('admin.staff.index') }}">
                    <input type="hidden" name="change" value="{{ $candidate->id }}">
                    @if ($search) <input type="hidden" name="search" value="{{ $search }}"> @endif
                    @if (request('page')) <input type="hidden" name="page" value="{{ request('page') }}"> @endif

                    <label class="block rounded-lg border border-[#283750] bg-[#0c1424] p-[14px] text-[13px] text-[#a0aec5]">
                        <span class="uppercase">New role</span>
                        <select name="role" onchange="this.form.submit()"
                            class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                            @foreach ($roleOptions as $option)
                                <option value="{{ $option->value }}" @selected($option === $newRole)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                </form>

                <p class="text-sm leading-relaxed text-[#a0aec5]">{{ $copy['body'] }}</p>

                <p class="text-sm text-[#a0aec5]">Current: {{ $candidate->role->label() }} → New: {{ $newRole->label() }}</p>

                <form method="POST" action="{{ route('admin.staff.role.update', $candidate) }}" class="flex items-center gap-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="role" value="{{ $newRole->value }}">

                    <a href="{{ route('admin.staff.index', request()->only(['search', 'page'])) }}"
                        class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-sm text-white hover:bg-[#1e2f4d]">Cancel</a>
                    <button type="submit"
                        class="rounded-lg border border-[#347bff] bg-[#347bff] px-5 py-[13px] text-sm text-white hover:bg-[#4a8bff]">
                        Confirm role change
                    </button>
                </form>

                <p class="text-xs text-[#a0aec5]">Recorded with actor, target and before/after role.</p>
            </section>
        @endif

        {{-- Protected roles --}}
        <section class="{{ $candidate ? '' : 'lg:col-span-2' }} flex flex-col gap-5 rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
            <h2 class="text-2xl font-bold text-[#f4f7ff]">Protected roles</h2>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4 text-sm">
                <p class="font-medium text-[#f4f7ff]">ⓘ You can’t change your own role</p>
                <p class="mt-1 text-[#a0aec5]">
                    {{ $actor->name }}’s role control is read-only while signed in as {{ \Illuminate\Support\Str::before($actor->name, ' ') }}.
                </p>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4 text-sm">
                <p class="font-medium text-[#f4f7ff]">! Keep at least one administrator</p>
                <p class="mt-1 text-[#a0aec5]">
                    @if ($lastAdmin)
                        {{ \Illuminate\Support\Str::before($lastAdmin->name, ' ') }} is the last admin. Demotion is blocked until another administrator exists.
                    @else
                        Demoting the last remaining administrator is always blocked.
                    @endif
                </p>
            </div>

            <p class="text-xs text-[#a0aec5]">
                Every successful role change is recorded in the audit log. The only roles are Customer, Staff and Admin.
            </p>
        </section>
    </div>
@endsection