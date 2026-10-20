<x-layouts::site :title="__('Hold expired')">
    <div class="mx-auto max-w-[1312px] px-6 pt-10 lg:px-0">
        <div class="rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 sm:p-12">

            <h2 class="text-3xl font-bold">Hold expired</h2>

            <div class="mt-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#f59e0b] text-2xl text-[#f59e0b]">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <p class="mt-6 text-xs uppercase tracking-wide text-[#9aa3b2]">! Expired · Seats released</p>

            <h1 class="mt-6 text-4xl font-bold leading-tight sm:text-5xl">Your time ran out.</h1>

            <p class="mt-6 text-sm text-[#9aa3b2]">
                The 10-minute hold expired at 18:30:00. No verified payment was received in time, so D7 and D8 were released together.
            </p>

            <div class="mt-6 space-y-5 text-sm text-[#9aa3b2]">
                <p>Booking · CB-081011-6682</p>
                <p>Movie · The Last Horizon · Screen 1 · 18:30</p>
            </div>

            <div class="mt-8 rounded-lg bg-[#17294f] p-4 text-sm leading-relaxed">
                <p class="font-semibold text-blue-300">ⓘ No ticket was issued</p>
                <p>Choose from the seats currently available. A new hold starts only when you press Hold seats again.</p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('home') }}"
                   class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6]">
                    Find available seats
                </a>
                <a href="{{ route('dashboard') }}"
                   class="rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c]">
                    My bookings
                </a>
            </div>
        </div>
    </div>
</x-layouts::site>