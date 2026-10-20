<x-layouts::site :title="__('Payment processing')">
    <div class="mx-auto max-w-[1312px] px-6 pt-10 lg:px-0">

        <p class="text-xs font-semibold uppercase tracking-wider text-[#7fb0ff]">
            Stripe return / verification
        </p>

        <div class="mt-8 grid items-center gap-8 lg:grid-cols-[minmax(0,832px)_1fr]">

            {{-- Left: status panel --}}
            <div>
                <div class="rounded-2xl border border-[#1f2a44] bg-[#0e1527] p-6">
                    <div class="h-14 w-14 animate-spin rounded-full border-2 border-[#2a3d66] border-t-[#7fb0ff]"></div>

                    <p class="mt-6 text-xs uppercase tracking-wide text-[#9aa3b2]">◷ Pending payment</p>

                    <h1 class="mt-5 text-4xl font-bold leading-tight">Payment processing</h1>

                    <p class="mt-5 text-sm text-[#9aa3b2]">
                        You're back from Stripe. We're waiting for verified payment before confirming your booking.
                    </p>

                    <div class="mt-5 rounded-lg bg-[#17294f] p-4 text-sm leading-relaxed">
                        <p>ⓘ This isn't a ticket confirmation</p>
                        <p>Please don't pay again. Your tickets will be available only when the booking status changes to Confirmed.</p>
                    </div>

                    <p class="mt-5 text-sm text-[#9aa3b2]">
                        Last checked 16:44:12 · Hold expires at 16:50:00 This page checks for updates automatically.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ url()->current() }}"
                           class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6]">
                            Check booking status
                        </a>
                        <a href="{{ route('dashboard') }}"
                           class="rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c]">
                            Go to my bookings
                        </a>
                    </div>
                </div>

                <p class="mt-5 text-sm text-[#9aa3b2]">
                    It's safe to leave this page. Your booking status is saved in My bookings.
                </p>
            </div>

            {{-- Right: booking summary (dummy data for now) --}}
            <div>
                <div class="flex gap-5 rounded-2xl border border-[#1f2a44] bg-[#0e1527] p-6">
                    <div class="h-[180px] w-[104px] shrink-0 rounded-lg bg-gradient-to-b from-[#0f6a9c] via-[#1d3b66] to-[#0a1224]"></div>
                    <div class="text-sm leading-7 text-[#9aa3b2]">
                        <h2 class="text-2xl font-bold leading-tight text-white">The Last Horizon</h2>
                        <p>Thu, October 15, 2026 · 19:30</p>
                        <p>CineBook Downtown · Screen 1</p>
                        <p>124 min · PG-13</p>
                        <p>G7 + G8 · Premium</p>
                        <p>CB-261015-0482</p>
                        <p>Total · $24.00 USD</p>
                    </div>
                </div>
                <p class="mt-4 text-sm text-[#9aa3b2]">
                    Reference CB-261015-0482 Stripe TEST MODE · $24.00 USD
                </p>
            </div>
        </div>
    </div>
</x-layouts::site>