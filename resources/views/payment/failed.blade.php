<x-layouts::site :title="__('Payment failed')">
    <div class="mx-auto max-w-[1312px] px-6 pt-10 lg:px-0">
        <div class="rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 sm:p-12">

            <h2 class="text-3xl font-bold">Payment failed</h2>

            <div class="mt-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#e5667a] text-2xl text-[#e5667a]">✕</div>

            <p class="mt-6 text-xs uppercase tracking-wide text-[#9aa3b2]">! Failed · No valid tickets</p>

            <h1 class="mt-6 text-4xl font-bold leading-tight sm:text-5xl">Let's try another way.</h1>

            <p class="mt-6 text-sm text-[#9aa3b2]">
                Stripe reported that your test payment failed. Your hold was released. None of your selected seats remain reserved.
            </p>

            <div class="mt-6 space-y-5 text-sm text-[#9aa3b2]">
                <p>Booking · CB-261015-0482</p>
                <p>Movie · The Last Horizon · Oct 15 · 19:30</p>
                <p>Payment · $24.00 · Failed</p>
            </div>

            <div class="mt-8 rounded-lg bg-[#17294f] p-4 text-sm leading-relaxed">
                <p class="font-semibold text-blue-300">ⓘ No confirmed booking</p>
                <p>G7 and G8 may no longer be available. Start a new seat selection before trying payment again.</p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('home') }}"
                   class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6]">
                    Choose seats again
                </a>
                <a href="{{ route('dashboard') }}"
                   class="rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c]">
                    My bookings
                </a>
            </div>
        </div>
    </div>
</x-layouts::site>