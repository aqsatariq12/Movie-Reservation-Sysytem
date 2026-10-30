@extends('layouts.admin')

@section('content')
    {{-- Heading --}}
    <div class="flex items-center gap-6">
        <div class="flex-1">
            <p class="text-xs font-semibold uppercase text-[#347bff]">
                Cinebook Downtown / Reservations
            </p>
            <h1 class="text-[32px] font-bold leading-normal text-[#f4f7ff]">
                Refunds
            </h1>
            <p class="mt-1 text-sm text-[#a0aec5]">
                Review and monitor customer refund requests.
            </p>
        </div>
    </div>
    {{-- Success message --}}
    @if (session('success'))
        <div class="rounded-lg border border-green-500/30 bg-green-500/10 p-4 text-sm text-green-300" role="status">
            {{ session('success') }}
        </div>
    @endif

    {{-- Validation and error messages --}}
    @if ($errors->any())
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300" role="alert">
            <p class="mb-1 font-semibold">Unable to retry refund</p>

            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300" role="alert">
            {{ session('error') }}
        </div>
    @endif
    {{-- Notice --}}
    <div class="rounded-lg border border-[#347bff]/30 bg-[#347bff]/10 p-4 text-sm leading-[1.6] text-[#f4f7ff]">
        <p class="font-semibold">Refund records</p>
        <p class="text-[#a0aec5]">
            Refund statuses are updated through verified Stripe events and reconciliation.
            Eligible failed or canceled refunds can be submitted for a new Stripe refund attempt.
        </p>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.refunds.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-3">

        {{-- Search --}}
        <label class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-3.5">
            <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">
                Refund / booking reference
            </span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search refund or booking..."
                class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none placeholder:text-[#a0aec5]">
        </label>

        {{-- Date range --}}
        <div class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-3.5">
            <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">
                Requested date
            </span>

            <div class="flex items-center gap-2">
                <input type="date" name="from" value="{{ request('from') }}" aria-label="From date"
                    class="w-full min-w-0 bg-transparent text-[13px] text-[#f4f7ff] outline-none [color-scheme:dark]">

                <span class="text-[13px] text-[#a0aec5]">–</span>

                <input type="date" name="to" value="{{ request('to') }}" aria-label="To date"
                    class="w-full min-w-0 bg-transparent text-[13px] text-[#f4f7ff] outline-none [color-scheme:dark]">
            </div>
        </div>

        {{-- Status --}}
        <label class="flex min-h-[76px] flex-col rounded-lg border border-[#283750] bg-[#0c1424] p-3.5">
            <span class="text-[13px] uppercase leading-[1.7] text-[#a0aec5]">
                Refund status
            </span>

            <select name="status" class="w-full bg-transparent text-[13px] text-[#f4f7ff] outline-none [color-scheme:dark]">
                <option value="" class="bg-[#0c1424]">All statuses</option>

                @foreach (\App\Enums\RefundStatus::cases() as $status)
                    <option value="{{ $status->value }}" class="bg-[#0c1424]" @selected(request('status') === $status->value)>
                        {{ ucfirst($status->value) }}
                    </option>
                @endforeach
            </select>
        </label>

        {{-- Filter buttons --}}
        <div class="flex flex-wrap items-center gap-3 md:col-span-3">
            <button type="submit"
                class="rounded-lg bg-[#347bff] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2f6fe6]">
                Apply filters
            </button>

            <a href="{{ route('admin.refunds.index') }}"
                class="rounded-lg border border-[#283750] px-5 py-2.5 text-sm text-[#a0aec5] hover:border-[#347bff] hover:text-white">
                Clear filters
            </a>
        </div>
    </form>

    {{-- Refunds table --}}
    <div class="overflow-x-auto rounded-xl border border-[#283750] bg-[#0c1424] p-4">
        <table class="w-full min-w-[1050px] table-fixed text-sm">
            <thead>
                <tr class="border-b border-[#283750] text-left text-xs font-semibold uppercase text-[#a0aec5]">
                    <th class="w-[13%] pb-3 pr-3">Refund</th>
                    <th class="w-[15%] pb-3 pr-3">Booking</th>
                    <th class="w-[15%] pb-3 pr-3">Customer</th>
                    <th class="w-[16%] pb-3 pr-3">Reason</th>
                    <th class="w-[12%] pb-3 pr-3">Amount</th>
                    <th class="w-[11%] pb-3 pr-3">Status</th>
                    <th class="w-[13%] pb-3 pr-3">Requested</th>
                    <th class="w-[5%] pb-3">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($refunds as $refund)
                        @php
                            $status = $refund->status instanceof \BackedEnum
                                ? $refund->status->value
                                : (string) $refund->status;

                            $statusStyles = match ($status) {
                                'succeeded' => 'border-green-500/30 bg-green-500/10 text-green-400',
                                'pending' => 'border-yellow-500/30 bg-yellow-500/10 text-yellow-400',
                                'failed' => 'border-red-500/30 bg-red-500/10 text-red-400',
                                'canceled' => 'border-[#283750] bg-[#283750]/30 text-[#a0aec5]',
                                default => 'border-[#283750] text-[#a0aec5]',
                            };

                            $payment = $refund->payment;
                            $booking = $payment?->booking;
                            $customer = $booking?->user;

                            $refundReference = 'REF-' . str_pad(
                                (string) $refund->id,
                                4,
                                '0',
                                STR_PAD_LEFT
                            );

                            $currency = strtoupper($payment?->currency ?? 'USD');
                            $currencyPrefix = $currency === 'USD' ? '$' : $currency . ' ';
                        @endphp

                        <tr class="border-b border-[#283750] last:border-b-0">
                            {{-- Refund reference --}}
                            <td class="truncate py-4 pr-3 font-semibold text-[#f4f7ff]" title="{{ $refundReference }}">
                                {{ $refundReference }}
                            </td>

                            {{-- Booking --}}
                            <td class="truncate py-4 pr-3 text-[#a0aec5]"
                                title="{{ $booking?->booking_reference ?? 'Unavailable' }}">
                                {{ $booking?->booking_reference ?? '—' }}
                            </td>

                            {{-- Customer --}}
                            <td class="truncate py-4 pr-3 text-[#a0aec5]" title="{{ $customer?->name ?? 'Unavailable' }}">
                                {{ $customer?->name ?? '—' }}
                            </td>

                            {{-- Reason --}}
                            <td class="truncate py-4 pr-3 text-[#a0aec5]" title="{{ $refund->reason ?? 'Not specified' }}">
                                {{ $refund->reason
                    ? \Illuminate\Support\Str::headline($refund->reason)
                    : '—' }}
                            </td>

                            {{-- Amount --}}
                            <td class="py-4 pr-3 font-medium text-[#f4f7ff]">
                                {{ $currencyPrefix }}{{ number_format($refund->amount_cents / 100, 2) }}
                            </td>

                            {{-- Status --}}
                            <td class="py-4 pr-3">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusStyles }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>

                            {{-- Requested date --}}
                            <td class="py-4 pr-3 text-[#a0aec5]">
                                {{ $refund->requested_at?->format('M j, Y') ?? '—' }}
                                @if ($refund->requested_at)
                                    <span class="block text-xs text-[#64748b]">
                                        {{ $refund->requested_at->format('H:i') }}
                                    </span>
                                @endif
                            </td>

{{-- Action --}}
<td class="py-4">
    <div class="flex items-center gap-2">
        <button type="button"
            data-refund-view
            data-refund-details="{{ json_encode([
                'reference' => $refundReference,
                'stripe_reference' => $refund->provider_refund_id ?? 'Unavailable',
                'booking' => $booking?->booking_reference ?? 'Unavailable',
                'customer' => $customer?->name ?? 'Unavailable',
                'email' => $customer?->email ?? 'Unavailable',
                'amount' => $currencyPrefix . number_format($refund->amount_cents / 100, 2),
                'currency' => $currency,
                'status' => ucfirst($status),
                'reason' => $refund->reason
                    ? \Illuminate\Support\Str::headline($refund->reason)
                    : 'Not specified',
                'requested' => $refund->requested_at?->format('M j, Y H:i') ?? 'Unavailable',
                'completed' => $refund->completed_at?->format('M j, Y H:i') ?? 'Not completed',
                'payment_reference' => $payment?->provider_payment_id ?? 'Unavailable',
                'payment_status' => $payment?->status?->value ?? $payment?->status ?? 'Unavailable',
            ]) }}"
            class="rounded-md border border-[#283750] px-3 py-1.5 text-xs font-semibold text-[#f4f7ff] transition hover:border-[#347bff] hover:bg-[#347bff]/10">
            View
        </button>

        @if (in_array($status, ['pending', 'failed', 'canceled']))
            <button type="button"
                data-refund-retry
                data-refund-retry-url="{{ route('admin.refunds.retry', $refund) }}"
                data-refund-reference="{{ $refundReference }}"
                data-refund-amount="{{ $currencyPrefix }}{{ number_format($refund->amount_cents / 100, 2) }}"
                class="rounded-md border border-[#347bff]/50 px-3 py-1.5 text-xs font-semibold text-[#87b4ff] transition hover:bg-[#347bff]/10">
                Retry
            </button>
        @endif
    </div>
</td>
                        </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center">
                            <p class="font-medium text-[#f4f7ff]">No refunds found</p>
                            <p class="mt-1 text-sm text-[#a0aec5]">
                                Try changing your filters or check again later.
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    {{ $refunds->links('vendor.pagination.cinebook') }}

   
{{-- Refund details modal --}}
<div id="refundViewModal"
    class="fixed inset-0 z-[60] hidden items-center justify-center overflow-y-auto bg-black/70 p-4"
    role="dialog" aria-modal="true" aria-labelledby="refundViewTitle">

    <div class="my-auto w-full max-w-2xl rounded-xl border border-[#283750] bg-[#101a2d] p-6 shadow-2xl">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-[#347bff]">
                    Cinebook Downtown / Refund Management
                </p>
                <h2 id="refundViewTitle" class="mt-2 text-xl font-bold text-[#f4f7ff]">
                    Refund details
                </h2>
                <p id="viewRefundReference" class="mt-1 text-sm text-[#a0aec5]"></p>
            </div>

            <button type="button" data-close-refund-view
                class="rounded-lg border border-[#283750] px-3 py-2 text-sm text-[#a0aec5] hover:bg-[#283750] hover:text-white">
                Close
            </button>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <p class="text-xs uppercase text-[#64748b]">Refund amount</p>
                <p id="viewRefundAmount" class="mt-2 text-xl font-bold text-[#f4f7ff]"></p>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <p class="text-xs uppercase text-[#64748b]">Status</p>
                <p id="viewRefundStatus" class="mt-2 font-semibold text-[#f4f7ff]"></p>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <p class="text-xs uppercase text-[#64748b]">Booking reference</p>
                <p id="viewRefundBooking" class="mt-2 break-words text-sm text-[#f4f7ff]"></p>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <p class="text-xs uppercase text-[#64748b]">Refund reason</p>
                <p id="viewRefundReason" class="mt-2 text-sm text-[#f4f7ff]"></p>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <p class="text-xs uppercase text-[#64748b]">Customer</p>
                <p id="viewRefundCustomer" class="mt-2 text-sm text-[#f4f7ff]"></p>
                <p id="viewRefundEmail" class="mt-1 break-all text-xs text-[#a0aec5]"></p>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <p class="text-xs uppercase text-[#64748b]">Stripe refund reference</p>
                <p id="viewStripeRefund" class="mt-2 break-all text-sm text-[#f4f7ff]"></p>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <p class="text-xs uppercase text-[#64748b]">Payment intent</p>
                <p id="viewPaymentReference" class="mt-2 break-all text-sm text-[#f4f7ff]"></p>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <p class="text-xs uppercase text-[#64748b]">Payment status</p>
                <p id="viewPaymentStatus" class="mt-2 text-sm text-[#f4f7ff]"></p>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <p class="text-xs uppercase text-[#64748b]">Requested date</p>
                <p id="viewRefundRequested" class="mt-2 text-sm text-[#f4f7ff]"></p>
            </div>

            <div class="rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <p class="text-xs uppercase text-[#64748b]">Completed date</p>
                <p id="viewRefundCompleted" class="mt-2 text-sm text-[#f4f7ff]"></p>
            </div>

        </div>

        <div class="mt-6 flex justify-end">
            <button type="button" data-close-refund-view
                class="rounded-lg bg-[#347bff] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2f6fe6]">
                Close details
            </button>
        </div>
    </div>
</div>

    {{-- Retry confirmation modal (UI only) --}}
    <div id="refundRetryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4" role="dialog"
        aria-modal="true" aria-labelledby="refundRetryTitle">

        <div class="w-full max-w-md rounded-xl border border-[#283750] bg-[#101a2d] p-6 shadow-2xl">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#87b4ff]">
                        Refund management
                    </p>

                    <h2 id="refundRetryTitle" class="mt-2 text-xl font-bold text-[#f4f7ff]">
                        Retry refund?
                    </h2>
                </div>

                <button type="button" data-close-refund-modal class="text-xl text-[#a0aec5] hover:text-white"
                    aria-label="Close">
                    &times;
                </button>
            </div>

            <p class="mt-4 text-sm leading-6 text-[#a0aec5]">
                You are reviewing a retry for the following refund:
            </p>

            <div class="mt-4 space-y-3 rounded-lg border border-[#283750] bg-[#0c1424] p-4">
                <div class="flex justify-between gap-4 text-sm">
                    <span class="text-[#a0aec5]">Refund reference</span>
                    <span id="retryRefundReference" class="font-semibold text-[#f4f7ff]"></span>
                </div>

                <div class="flex justify-between gap-4 text-sm">
                    <span class="text-[#a0aec5]">Refund amount</span>
                    <span id="retryRefundAmount" class="font-semibold text-[#f4f7ff]"></span>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-yellow-500/30 bg-yellow-500/10 p-3">
                <p class="text-sm leading-6 text-yellow-300">
                    This will submit a new refund attempt to Stripe for the displayed amount.
                    The original refund record will remain unchanged.
                </p>
            </div>

            <form id="refundRetryForm" method="POST" class="mt-6 flex justify-end gap-3">
                @csrf

                <button type="button" data-close-refund-modal
                    class="rounded-lg border border-[#283750] px-5 py-2.5 text-sm font-semibold text-[#a0aec5] hover:bg-[#283750]">
                    Cancel
                </button>

                <button type="submit"
                    class="rounded-lg bg-[#347bff] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2f6fe6]">
                    Confirm retry
                </button>
            </form>


        </div>
    </div>

    <script>
       
document.addEventListener('DOMContentLoaded', function () {
    // Refund details modal
    const viewModal = document.getElementById('refundViewModal');

    if (viewModal) {
        const viewFields = {
            reference: 'viewRefundReference',
            amount: 'viewRefundAmount',
            status: 'viewRefundStatus',
            booking: 'viewRefundBooking',
            reason: 'viewRefundReason',
            customer: 'viewRefundCustomer',
            email: 'viewRefundEmail',
            stripe_reference: 'viewStripeRefund',
            payment_reference: 'viewPaymentReference',
            payment_status: 'viewPaymentStatus',
            requested: 'viewRefundRequested',
            completed: 'viewRefundCompleted',
        };

        document.querySelectorAll('[data-refund-view]').forEach(function (button) {
            button.addEventListener('click', function () {
                let details;

                try {
                    details = JSON.parse(button.dataset.refundDetails);
                } catch (error) {
                    console.error('Unable to read refund details:', error);
                    return;
                }

                Object.entries(viewFields).forEach(function ([key, elementId]) {
                    const element = document.getElementById(elementId);

                    if (element) {
                        element.textContent = details[key] ?? 'Unavailable';
                    }
                });

                viewModal.classList.remove('hidden');
                viewModal.classList.add('flex');
            });
        });

        function closeViewModal() {
            viewModal.classList.add('hidden');
            viewModal.classList.remove('flex');
        }

        viewModal.querySelectorAll('[data-close-refund-view]').forEach(function (button) {
            button.addEventListener('click', closeViewModal);
        });

        viewModal.addEventListener('click', function (event) {
            if (event.target === viewModal) {
                closeViewModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeViewModal();
            }
        });
    }

    // Retry refund modal
    const modal = document.getElementById('refundRetryModal');

    if (modal) {
        const reference = document.getElementById('retryRefundReference');
        const amount = document.getElementById('retryRefundAmount');
        const retryForm = document.getElementById('refundRetryForm');

        document.querySelectorAll('[data-refund-retry]').forEach(function (button) {
            button.addEventListener('click', function () {
                reference.textContent = button.dataset.refundReference;
                amount.textContent = button.dataset.refundAmount;
                retryForm.action = button.dataset.refundRetryUrl;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        modal.querySelectorAll('[data-close-refund-modal]').forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    }
});

    </script>

@endsection