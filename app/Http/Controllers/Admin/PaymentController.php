<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentIndexRequest;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Booking;
use App\Models\WebhookEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PaymentController extends Controller
{
    /** Rows per page (the Figma screen shows 6). */
    private const PER_PAGE = 6;

    /** Tab key => [label, payment statuses it covers (null = all)]. */
    private const TABS = [
        'all'        => ['All payments', null],
        'succeeded'  => ['Succeeded', ['paid']],
        'processing' => ['Processing', ['pending']],
        'failed'     => ['Failed', ['failed']],
    ];

    public function index(PaymentIndexRequest $request)
    {
        $payments = $this->filtered($request)
            ->with('booking.user:id,name,email')
            ->orderByRaw('COALESCE(payments.paid_at, payments.created_at) DESC')
            ->orderByDesc('payments.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'tab'      => $request->tab(),
            'tabs'     => collect(self::TABS)->map(fn ($t) => $t[0])->all(),
            'statuses' => PaymentStatus::cases(),
        ]);
    }

    /** CSV of every payment matching the active filters (not just the current page). */
    public function export(PaymentIndexRequest $request): StreamedResponse
    {
        $filename = 'payments-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads it correctly

            fputcsv($out, [
                'Payment', 'Booking', 'Customer', 'Email',
                'Amount', 'Currency', 'Status', 'Updated',
            ], ',', '"', '\\');

            $this->filtered($request)
                ->with('booking.user:id,name,email')
                ->chunkById(200, function ($chunk) use ($out) {
                    foreach ($chunk as $p) {
                        fputcsv($out, array_map([$this, 'csvSafe'], [
                            'PAY-' . str_pad((string) $p->id, 4, '0', STR_PAD_LEFT),
                            $p->booking?->booking_reference ?? '',
                            $p->booking?->user?->name ?? '',
                            $p->booking?->user?->email ?? '',
                            number_format($p->amount_cents / 100, 2, '.', ''),
                            $p->currency,
                            $p->adminState()['label'],
                            ($p->paid_at ?? $p->created_at)?->format('Y-m-d H:i') ?? '',
                        ]), ',', '"', '\\');
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }


        /** Read-only payment detail. */
    public function show(Payment $payment)
    {
        $payment->load(['booking.user:id,name,email', 'booking.tickets', 'refunds']);

        $booking = $payment->booking;
        $refunds = $payment->refunds->sortBy('requested_at')->values();

        $refundedCents = (int) $refunds
            ->filter(fn ($r) => $this->val($r->status) === 'succeeded')
            ->sum('amount_cents');

        // ---- Verified provider events (stored only after signature check) ----
        $events = WebhookEvent::query()
            ->where('booking_id', $payment->booking_id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (WebhookEvent $e) => [
                'time'   => $e->created_at?->format('H:i:s') ?? '—',
                'type'   => $e->event_type,
                'result' => match ($this->val($e->status)) {
                    'processed'  => 'Applied once',
                    'ignored'    => 'Ignored',
                    'processing' => 'Processing',
                    default      => ucfirst($this->val($e->status)),
                },
            ]);

        // ---- Reconciliation timeline (newest first) ----
        $items = [];
        $add = function (?CarbonInterface $at, string $text) use (&$items) {
            if ($at) {
                $items[] = ['at' => $at, 'text' => $text, 'seq' => count($items)];
            }
        };

        $add($payment->created_at, 'Checkout initiated');
        $add($payment->paid_at, 'Payment verified');

        if ($payment->status === PaymentStatus::Failed) {
            $add($payment->updated_at, 'Payment failed');
        }
        if ($payment->status === PaymentStatus::Expired) {
            $add($payment->updated_at, 'Checkout expired');
        }
        if ($booking && $booking->tickets->isNotEmpty()) {
            $add(Carbon::parse($booking->tickets->min('created_at')), 'Seats sold; tickets issued');
        }

        $add($booking?->confirmed_at, 'Booking confirmed');
        $add($booking?->cancelled_at, 'Booking cancelled');

        foreach ($refunds as $r) {
            $amount = $this->money((int) $r->amount_cents, $payment->currency);

            match ($this->val($r->status)) {
                'succeeded' => $add($r->completed_at ?? $r->requested_at, "Refund of {$amount} completed"),
                'pending'   => $add($r->requested_at, "Refund of {$amount} requested"),
                default     => null,
            };
        }

        $timeline = collect($items)
            ->sortBy([
                fn ($a, $b) => $b['at']->getTimestamp() <=> $a['at']->getTimestamp(),
                fn ($a, $b) => $b['seq'] <=> $a['seq'],
            ])
            ->map(fn ($e) => [
                'time' => $e['at']->isSameDay($payment->created_at)
                    ? $e['at']->format('H:i:s')
                    : $e['at']->format('M j · H:i:s'),
                'text' => $e['text'],
            ])
            ->values();

        return view('admin.payments.show', [
            'payment'        => $payment,
            'booking'        => $booking,
            'state'          => $payment->adminState(),
            'bookingState'   => $booking?->adminState(),
            'amount'         => $this->money((int) $payment->amount_cents, $payment->currency),
            'refundedAmount' => $this->money($refundedCents, $payment->currency),
            'events'         => $events,
            'timeline'       => $timeline,
            'reconciliation' => $this->reconciliation($payment, $booking, $refundedCents, $refunds),
        ]);
    }

    // ---- helpers ------------------------------------------------------------

    private function filtered(PaymentIndexRequest $request): Builder
    {
        $statuses = self::TABS[$request->tab()][1];
        $term = trim((string) $request->validated('search'));

        return Payment::query()
            ->when($term !== '', function (Builder $q) use ($term) {
                // "PAY-0482" or "482" style lookups go straight to the payment id
                if (preg_match('/^PAY-?0*(\d+)$/i', $term, $m)) {
                    $q->where('payments.id', (int) $m[1]);

                    return;
                }

                $like = '%' . addcslashes($term, '%_\\') . '%';

                $q->where(fn (Builder $w) => $w
                    ->where('payments.provider_payment_id', 'like', $like)
                    ->orWhereHas('booking', fn ($b) => $b->where('bookings.booking_reference', 'like', $like))
                    ->orWhereHas('booking.user', fn ($u) => $u->search($term)));
            })
            ->when($request->filled('from'), fn (Builder $q) => $q->whereRaw(
                'COALESCE(payments.paid_at, payments.created_at) >= ?',
                [Carbon::parse($request->validated('from'))->startOfDay()->toDateTimeString()]
            ))
            ->when($request->filled('to'), fn (Builder $q) => $q->whereRaw(
                'COALESCE(payments.paid_at, payments.created_at) <= ?',
                [Carbon::parse($request->validated('to'))->endOfDay()->toDateTimeString()]
            ))
            ->when($request->filled('status'), fn (Builder $q) => $q
                ->where('payments.status', $request->validated('status')))
            ->when($statuses, fn (Builder $q) => $q
                ->whereIn('payments.status', $statuses));
    }

    /** Stops spreadsheet formula injection from customer-controlled text. */
    private function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }


        /** Enum-or-string to plain string. */
    private function val(mixed $v): string
    {
        return $v instanceof \BackedEnum ? (string) $v->value : (string) $v;
    }

    private function money(int $cents, string $currency): string
    {
        $prefix = strtoupper($currency) === 'USD' ? '$' : strtoupper($currency) . ' ';

        return $prefix . number_format($cents / 100, 2);
    }

    /**
     * Does what Stripe says match what the booking says?
     *
     * @return array{tone: string, icon: string, headline: string, message: string}
     */
    private function reconciliation(Payment $payment, ?Booking $booking, int $refundedCents, Collection $refunds): array
    {
        $bookingStatus = $booking ? $this->val($booking->status) : null;

        $amountMatches = $booking !== null
            && (int) $booking->total_amount_cents === (int) $payment->amount_cents
            && strtoupper((string) $booking->currency) === strtoupper((string) $payment->currency);

        $refundInFlight = $refunds->contains(
            fn ($r) => in_array($this->val($r->status), ['pending', 'succeeded'], true)
        );

        $r = fn (string $tone, string $icon, string $headline, string $message) => compact('tone', 'icon', 'headline', 'message');

        return match ($payment->status) {
            PaymentStatus::Paid => match (true) {
                ! $amountMatches => $r('warn', '⚠', 'Needs review · amount mismatch',
                    'The payment amount or currency differs from the booking total.'),
                $bookingStatus === 'cancelled' && $refundInFlight => $r('muted', '•', 'Refund in progress',
                    'The payment arrived after the booking was cancelled. A refund has been requested.'),
                $bookingStatus === 'cancelled' => $r('warn', '⚠', 'Needs review · refund missing',
                    'The payment arrived after the booking was cancelled and no refund exists yet.'),
                $refundedCents > 0 => $r('muted', '•', 'Partially refunded',
                    $this->money($refundedCents, $payment->currency) . ' of '
                    . $this->money((int) $payment->amount_cents, $payment->currency) . ' has been refunded.'),
                $bookingStatus === 'confirmed' => $r('ok', '✓', 'Matched · healthy',
                    'Booking amount matches the verified provider payment. Confirmed after the payment event, not the browser redirect.'),
                default => $r('warn', '⚠', 'Needs review',
                    'The payment succeeded but the booking is "' . ($booking?->adminState()['label'] ?? 'unknown') . '".'),
            },

            PaymentStatus::Refunded => $r('muted', '•', 'Refunded · settled',
                'The full amount was refunded and the booking is marked refunded.'),

            PaymentStatus::Pending => $payment->isStuck()
                ? $r('warn', '⚠', 'Needs review · stuck',
                    'No verified provider event after ' . Payment::STUCK_AFTER_MINUTES . ' minutes. Check Failed jobs and the Stripe dashboard.')
                : $r('muted', '•', 'Awaiting provider confirmation',
                    'Waiting for a verified Stripe event. Seats stay held until the hold expires.'),

            PaymentStatus::Failed => $r('muted', '•', 'Closed · payment failed',
                'No funds were captured for this payment.'),

            PaymentStatus::Expired => $r('muted', '•', 'Closed · checkout expired',
                'The customer did not complete checkout. No funds were captured.'),
        };
    }
    
}