<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Bookings\AdminCancelBookingAction;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingIndexRequest;
use App\Http\Requests\Admin\CancelBookingRequest;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    /** Rows per page (the Figma screen shows 7). */
    private const PER_PAGE = 7;

    /** Tab key => [label, booking statuses it covers (null = all)]. */
    private const TABS = [
        'all' => ['All', null],
        'pending' => ['Pending payment', ['pending_payment']],
        'confirmed' => ['Confirmed', ['confirmed']],
        'cancelled' => ['Cancelled / refunded', ['cancelled', 'refunded']],
    ];

    public function index(BookingIndexRequest $request)
    {
        $tab = $request->tab();

        $bookings = $this->filtered($request)
            ->with($this->relations())
            ->latest('bookings.created_at')
            ->latest('bookings.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $bookings->getCollection()->each(
            fn(Booking $b) => $b->setAttribute('seat_summary', $this->seatSummary($b))
        );

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'tab' => $tab,
            'tabs' => collect(self::TABS)->map(fn($t) => $t[0])->all(),
            'counts' => $this->tabCounts($request),
            'statuses' => BookingStatus::cases(),
            'movies' => Movie::orderBy('title')->get(['id', 'title']),
        ]);
    }

    /** CSV of every booking matching the active filters (not just the current page). */
    public function export(BookingIndexRequest $request): StreamedResponse
    {
        $filename = 'bookings-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads it correctly

            fputcsv($out, [
                'Reference',
                'Customer',
                'Email',
                'Movie',
                'Show start',
                'Seats',
                'Total (USD)',
                'Status',
                'Booked at',
            ], ',', '"', '\\');

            $this->filtered($request)
                ->with($this->relations())
                ->chunkById(200, function ($chunk) use ($out) {
                    foreach ($chunk as $b) {
                        fputcsv($out, array_map([$this, 'csvSafe'], [
                            $b->booking_reference,
                            $b->user?->name ?? '',
                            $b->user?->email ?? '',
                            $b->showtime?->movie?->title ?? '',
                            $b->showtime?->starts_at?->format('Y-m-d H:i') ?? '',
                            $this->seatSummary($b),
                            number_format($b->total_amount_cents / 100, 2, '.', ''),
                            $b->adminState()['label'],
                            $b->created_at?->format('Y-m-d H:i') ?? '',
                        ]), ',', '"', '\\');
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Read-only booking detail. */
    public function show(Booking $booking)
    {
        $booking->load([
            'user',
            'showtime.movie',
            'showtime.screen',
            'bookingSeats.seat',
            'hold',
            'payments.refunds',
            'tickets',
        ]);

        $showtime = $booking->showtime;
        $movie = $showtime->movie;
        $status = $this->val($booking->status);
        $suffix = Str::afterLast($booking->booking_reference, '-');

        // ---- Seats & admission (one ticket per seat) ----------------------
        $bookingSeats = $booking->bookingSeats
            ->filter(fn($bs) => $bs->seat)
            ->sortBy(fn($bs) => $bs->seat->row_label . str_pad((string) $bs->seat->seat_number, 3, '0', STR_PAD_LEFT))
            ->values();

        $tickets = $booking->tickets->keyBy('booking_seat_id');

        $seatRows = $bookingSeats->map(function ($bs, $i) use ($tickets, $suffix) {
            $ticket = $tickets->get($bs->id);
            $ticketStatus = $ticket ? $this->val($ticket->status) : null;

            return [
                'seat' => $bs->seat->row_label . $bs->seat->seat_number . ' / ' . ucfirst($this->val($bs->seat->type)),
                'ticket' => $ticket ? sprintf('T-%s-%02d', $suffix, $i + 1) : '—',
                'scan' => match ($ticketStatus) {
                    'used' => 'Scanned',
                    'void' => 'Void',
                    'valid' => 'Not scanned',
                    default => '—',
                },
                'scanned_at' => $ticket?->used_at ? Carbon::parse($ticket->used_at)->format('M j · H:i') : '—',
            ];
        });

        $seatNames = $bookingSeats->map(fn($bs) => $bs->seat->row_label . $bs->seat->seat_number);
        $types = $bookingSeats->map(fn($bs) => ucfirst($this->val($bs->seat->type)))->unique();
        $typeLabel = $types->count() === 1 ? $types->first() : 'Mixed seating';
        $scannedCount = $tickets->filter(fn($t) => $this->val($t->status) === 'used')->count();

        // ---- Status line --------------------------------------------------
        $summary = match ($status) {
            'confirmed' => 'Payment verified'
                . ($booking->confirmed_at ? ' · ' . $booking->confirmed_at->format('M j \a\t H:i') : '')
                . ' · ' . ($scannedCount > 0
                ? $scannedCount . ' scanned ' . Str::plural('ticket', $scannedCount)
                : 'No scanned tickets'),
            'pending_payment' => 'Awaiting payment · seats held until the hold expires',
            'failed' => 'Payment failed · seats released',
            'expired' => 'Hold expired · seats released',
            default => 'Cancelled'
                . ($booking->cancelled_at ? ' · ' . $booking->cancelled_at->format('M j \a\t H:i') : ''),
        };

        // ---- Payment & refund history -------------------------------------
        $payments = $booking->payments->sortBy('created_at')->values();
        $refunds = $payments->flatMap(fn($p) => $p->refunds)->sortBy('requested_at')->values();

        $paymentRows = $payments->map(function ($p, $i) use ($suffix) {
            $pStatus = $this->val($p->status);

            return [
                'record' => 'PAY-' . $suffix . ($i > 0 ? '-' . ($i + 1) : '') . ' · Stripe test',
                'amount' => Showtime::money($p->amount_cents) . ' ' . $p->currency,
                'state' => $pStatus === 'paid' ? 'Succeeded' : ucfirst($pStatus),
                'time' => ($p->paid_at ?? $p->created_at)?->format('M j · H:i') ?? '—',
            ];
        })->all();

        $refundRows = $refunds->map(fn($r, $i) => [
            'record' => 'Refund' . ($refunds->count() > 1 ? ' #' . ($i + 1) : ''),
            'amount' => Showtime::money($r->amount_cents) . ' ' . $booking->currency,
            'state' => ucfirst($this->val($r->status)),
            'time' => Carbon::parse($r->completed_at ?? $r->requested_at)->format('M j · H:i'),
        ])->all();

        if (!$refundRows) {
            $refundRows[] = ['record' => 'Refund', 'amount' => '—', 'state' => 'None requested', 'time' => '—'];
        }

        // ---- Event timeline (newest first) --------------------------------
        $seatSentence = \Illuminate\Support\Arr::join($seatNames->all(), ', ', ' and ');
        $events = [];
        $add = function (?\Carbon\CarbonInterface $at, string $text) use (&$events) {
            if ($at) {
                $events[] = ['at' => $at, 'text' => $text, 'seq' => count($events)];
            }
        };

        // Added in the order things happen, so ties on the same minute stay in the right order.
        $add($booking->created_at, 'Booking created');
        if ($booking->hold) {
            $add(Carbon::parse($booking->hold->created_at), $seatSentence . ' held');
            if ($this->val($booking->hold->status) === 'expired') {
                $add(Carbon::parse($booking->hold->expires_at), 'Hold expired');
            }
        }
        if ($booking->tickets->isNotEmpty()) {
            $n = $booking->tickets->count();
            $add(Carbon::parse($booking->tickets->min('created_at')), $n . ' ' . Str::plural('ticket', $n) . ' issued');
        }
        foreach ($payments as $p) {
            if ($p->paid_at) {
                $add(Carbon::parse($p->paid_at), 'Payment webhook verified');
            }
            if ($this->val($p->status) === 'failed') {
                $add(Carbon::parse($p->updated_at), 'Payment failed');
            }
        }
        $add($booking->confirmed_at, 'Booking confirmed');
        $add($booking->cancelled_at, 'Booking cancelled');
        foreach ($refunds as $r) {
            $rStatus = $this->val($r->status);
            if ($rStatus === 'succeeded' && $r->completed_at) {
                $add(Carbon::parse($r->completed_at), 'Refund of ' . Showtime::money($r->amount_cents) . ' completed');
            } elseif ($rStatus === 'pending') {
                $add(Carbon::parse($r->requested_at), 'Refund of ' . Showtime::money($r->amount_cents) . ' requested');
            }
        }

        $timeline = collect($events)
            ->sortBy([
                fn($a, $b) => $b['at']->getTimestamp() <=> $a['at']->getTimestamp(),
                fn($a, $b) => $b['seq'] <=> $a['seq'],
            ])
            ->map(fn($e) => [
                'time' => $e['at']->isSameDay($booking->created_at) ? $e['at']->format('H:i') : $e['at']->format('M j · H:i'),
                'text' => $e['text'],
            ])
            ->values();

        return view('admin.bookings.show', [
            'booking' => $booking,
            'movie' => $movie,
            'showtime' => $showtime,
            'state' => $booking->adminState(),
            'status' => $status,
            'summary' => $summary,
            'seatSummary' => $seatNames->join(' + '),
            'typeLabel' => $typeLabel,
            'seatRows' => $seatRows,
            'paymentRows' => array_merge($paymentRows, $refundRows),
            'timeline' => $timeline,
            'total' => Showtime::money($booking->total_amount_cents),
            // Mail delivery is not tracked in the database yet (queued emails flow).
            // Set to true to preview the "Email delayed" notice from the design.
            'emailPending' => false,
        ]);
    }

    // ---- Admin booking cancellation -----------------------------------------

    /** Display the cancellation review screen. */
    public function confirmCancel(Booking $booking)
    {
        abort_unless(
            $this->val($booking->status) === 'confirmed',
            403,
            'Only confirmed bookings can be cancelled here.'
        );

        $booking->load([
            'user',
            'showtime.movie',
            'showtime.screen',
            'bookingSeats.seat',
            'payments.refunds',
            'tickets',
        ]);

        $showtime = $booking->showtime;

        $payment = $booking->payments
            ->filter(
                fn($p) =>
                    $p->provider === 'stripe'
                    && $this->val($p->status) === PaymentStatus::Paid->value
                    && filled($p->provider_payment_id)
            )
            ->sortByDesc('created_at')
            ->first();

        abort_unless(
            $payment,
            422,
            'A successful Stripe payment could not be verified for this booking.'
        );

        $seatNames = $booking->bookingSeats
            ->filter(fn($seat) => $seat->seat)
            ->sortBy(
                fn($seat) =>
                    $seat->seat->row_label
                    . str_pad((string) $seat->seat->seat_number, 3, '0', STR_PAD_LEFT)
            )
            ->map(
                fn($seat) =>
                    $seat->seat->row_label . $seat->seat->seat_number
            )
            ->values();

        $alreadyUsed = $booking->tickets->contains(
            fn($ticket) =>
                $this->val($ticket->status) === 'used'
                || $ticket->used_at !== null
                || $ticket->scans()->exists()
        );

        $existingRefund = $payment->refunds
            ->contains(
                fn($refund) =>
                    in_array(
                        $this->val($refund->status),
                        ['pending', 'succeeded'],
                        true
                    )
            );

        return view('admin.bookings.cancel', [
            'booking' => $booking,
            'showtime' => $showtime,
            'movie' => $showtime->movie,
            'payment' => $payment,
            'seatNames' => $seatNames,
            'refundAmount' => Showtime::money($payment->amount_cents),
            'canCancel' => !$alreadyUsed && !$existingRefund,
        ]);
    }

    /** Cancel the booking and request its Stripe refund immediately. */
    public function cancel(
        CancelBookingRequest $request,
        Booking $booking,
        AdminCancelBookingAction $action
    ) {
        $refund = $action->execute(
            $booking,
            (int) $request->user()->id,
            $request->validated('reason'),
            $request->ip()
        );

        $refundStatus = $this->val($refund->status);

        $message = match ($refundStatus) {
            'succeeded' => 'Booking cancelled successfully. The Stripe refund was successful.',
            'pending' => 'Booking cancelled. The Stripe refund is pending and will be checked by reconciliation.',
            'failed' => 'Booking cancelled, but the Stripe refund failed. Please check the payment and refund details.',
            'canceled' => 'Booking cancelled, but the Stripe refund was canceled. Please check the payment and refund details.',
            default => 'Booking cancelled. The Stripe refund status is being checked.',
        };

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', $message);
    }

    // ---- helpers ------------------------------------------------------------

    /** Enum-or-string to plain string. */
    private function val(mixed $v): string
    {
        return $v instanceof \BackedEnum ? (string) $v->value : (string) $v;
    }

    private function relations(): array
    {
        return [
            'user:id,name,email',
            'showtime.movie:id,title',
            'bookingSeats.seat',
            'payments.refunds', // needed by Booking::adminState()
        ];
    }

    /**
     * Filtered query. The tab and status dropdown are optional so the tab
     * counts can ignore them.
     */
    private function filtered(BookingIndexRequest $request, bool $applyTab = true, bool $applyStatus = true): Builder
    {
        $statuses = self::TABS[$request->tab()][1] ?? null;

        return Booking::query()
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $term = trim($request->validated('search'));
                $like = '%' . addcslashes($term, '%_\\') . '%';

                $q->where(fn(Builder $w) => $w
                    ->where('bookings.booking_reference', 'like', $like)
                    ->orWhereHas('user', fn($u) => $u->search($term)));
            })
            ->when($request->filled('movie_id'), fn(Builder $q) => $q
                ->whereHas('showtime', fn($s) => $s->where('movie_id', $request->integer('movie_id'))))
            ->when($request->filled('from'), fn(Builder $q) => $q
                ->where('bookings.created_at', '>=', Carbon::parse($request->validated('from'))->startOfDay()))
            ->when($request->filled('to'), fn(Builder $q) => $q
                ->where('bookings.created_at', '<=', Carbon::parse($request->validated('to'))->endOfDay()))
            ->when($applyStatus && $request->filled('status'), fn(Builder $q) => $q
                ->where('bookings.status', $request->validated('status')))
            ->when($applyTab && $statuses, fn(Builder $q) => $q
                ->whereIn('bookings.status', $statuses));
    }

    /** Counts per tab, following the search / date / movie filters. */
    private function tabCounts(BookingIndexRequest $request): array
    {
        // toBase() skips the enum cast, so the status stays a plain string key.
        $byStatus = $this->filtered($request, applyTab: false, applyStatus: false)
            ->toBase()
            ->selectRaw('bookings.status, COUNT(*) as total')
            ->groupBy('bookings.status')
            ->pluck('total', 'status');

        return collect(self::TABS)->map(
            fn($tab) => $tab[1] === null
                ? (int) $byStatus->sum()
                : (int) collect($tab[1])->sum(fn($s) => $byStatus[$s] ?? 0)
        )->all();
    }

    /** "G7, G8" in natural seat order. */
    private function seatSummary(Booking $booking): string
    {
        return $booking->bookingSeats
            ->filter(fn($bs) => $bs->seat)
            ->sortBy(fn($bs) => $bs->seat->row_label . str_pad((string) $bs->seat->seat_number, 3, '0', STR_PAD_LEFT))
            ->map(fn($bs) => $bs->seat->row_label . $bs->seat->seat_number)
            ->join(', ');
    }

    /** Stops spreadsheet formula injection from customer-controlled text. */
    private function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}