@extends('layouts.admin')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase leading-[1.5] text-[#87beff]">Cinebook Downtown / People</p>
        <h1 class="text-[32px] font-bold leading-[1.5] text-[#f4f7ff]">Customers</h1>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-700 bg-red-900/40 p-4 text-sm text-red-200">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('admin.customers.index') }}" class="grid gap-6 md:grid-cols-2">
        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Search by name or email</span>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search customers…"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]">
        </label>

        <label class="block rounded-lg border border-[#283750] bg-[#101a2d] p-[14px] text-[13px] text-[#a0aec5]">
            <span class="uppercase">Email verification</span>
            <select name="verification" onchange="this.form.submit()"
                class="mt-1 w-full bg-transparent text-[#f4f7ff] outline-none [&>option]:bg-[#0c1424]">
                <option value="all" @selected(request('verification', 'all') === 'all')>All customers</option>
                <option value="verified" @selected(request('verification') === 'verified')>Verified</option>
                <option value="unverified" @selected(request('verification') === 'unverified')>Unverified</option>
            </select>
        </label>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-[#283750] bg-[#101a2d] p-6">
        <table class="w-full min-w-[760px] text-left text-[13px] text-[#f4f7ff]">
            <thead class="text-[#a0aec5]">
                <tr class="uppercase">
                    <th class="pb-5 font-normal">Name</th>
                    <th class="pb-5 font-normal">Email</th>
                    <th class="pb-5 font-normal">Verification</th>
                    <th class="pb-5 font-normal">Bookings</th>
                    <th class="pb-5 font-normal">Total spent¹</th>
                    <th class="pb-5 font-normal text-[#87beff]">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr class="border-t border-[#283750]">
                        <td class="py-4">{{ $customer->name }}</td>
                        <td class="py-4 text-[#a0aec5]">{{ $customer->email }}</td>
                        <td class="py-4 text-[#a0aec5]">
                            {{ $customer->email_verified_at ? 'Verified' : 'Unverified' }}
                        </td>
                        <td class="py-4 text-[#a0aec5]">{{ $customer->bookings_count }}</td>
                        <td class="py-4 text-[#a0aec5]">
                            {{ \App\Models\Showtime::money(max(0, $customer->paid_cents - $customer->refunded_cents)) }}
                        </td>
                        <td class="py-4">
                            @if (Route::has('admin.customers.show'))
                                <a href="{{ route('admin.customers.show', $customer) }}"
                                    class="text-[#87beff] hover:underline">View →</a>
                            @else
                                <span class="text-[#a0aec5]/50">View →</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-[#a0aec5]">No customers match your filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between gap-4 text-sm">
        <p class="text-xs text-[#a0aec5]">
            Showing {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} of
            {{ $customers->total() }} customers
        </p>

        <div class="flex items-center gap-4">
            @if ($customers->previousPageUrl())
                <a href="{{ $customers->previousPageUrl() }}"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">← Previous</a>
            @else
                <span class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b]/50 px-5 py-[13px] text-white/40">← Previous</span>
            @endif

            <span class="text-xs text-[#87beff]">• {{ $customers->currentPage() }}</span>

            @if ($customers->nextPageUrl())
                <a href="{{ $customers->nextPageUrl() }}"
                    class="rounded-lg border border-[#283750] bg-[#17243b] px-5 py-[13px] text-white hover:bg-[#1e2f4d]">Next →</a>
            @else
                <span class="cursor-not-allowed rounded-lg border border-[#283750] bg-[#17243b]/50 px-5 py-[13px] text-white/40">Next →</span>
            @endif
        </div>
    </div>

    <p class="text-xs text-[#a0aec5]">¹ Total spent is verified payment less succeeded refunds · USD · Test data.</p>

    <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4 text-sm text-[#f4f7ff]">
        <p class="font-medium">ⓘ Customer records are retained</p>
        <p class="mt-1 text-[#a0aec5]">View account information and booking history. Customer deletion is not available.</p>
    </div>
@endsection