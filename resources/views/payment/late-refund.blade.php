<x-layouts::site :title="__('Late payment - Booking failed')">
    <div class="mx-auto max-w-[1312px] px-6 pt-10 lg:px-0">
        <div class="rounded-2xl border border-[#1f2a44] bg-[#0a1020] p-8 sm:p-12">

            <h2 class="text-3xl font-bold">Late payment · booking failed</h2>

            <div class="mt-6 flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#f43f5e] text-2xl font-bold text-[#f43f5e]">!</div>

            <p class="mt-6 text-xs uppercase tracking-wide text-[#9aa3b2]">! Failed · Refund pending</p>

            <h1 class="mt-6 text-4xl font-bold leading-tight sm:text-5xl">Paid too late. We're refunding it.</h1>

            <p class="mt-6 text-sm text-[#9aa3b2]">
                Payment arrived after the hold expired and D7/D8 were no longer available. Your booking failed. You do not have tickets.
            </p>

            <div class="mt-6 space-y-5 text-sm text-[#9aa3b2]">
                <p>Verified late payment · $26.00 (USD - 18:32)</p>
                <p>Automatic full refund · $26.00 - Pending</p>
                <p>Booking · CB-081011-6682</p>
            </div>

            <div class="mt-8 rounded-lg border border-rose-900/50 bg-rose-950/30 p-4 text-sm leading-relaxed text-rose-200">
                <p class="font-semibold">! Not a confirmed reservation</p>
                <p>The full refund has been requested automatically. You can track it in booking details; refund completion is still pending.</p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('dashboard') }}"
                   class="rounded-lg border border-[#1f2a44] bg-[#111b30] px-5 py-3 text-sm font-medium text-white hover:bg-[#16223c]">
                    View refund status
                </a>
                <a href="{{ route('home') }}"
                   class="rounded-lg bg-[#2f6bff] px-5 py-3 text-sm font-medium text-white hover:bg-[#2459d6]">
                    Choose a new show
                </a>
            </div>
        </div>
    </div>
</x-layouts::site>