<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Actions\Bookings\CancelBookingAction;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $filter = in_array($request->query('filter'), ['upcoming', 'past'], true)
            ? $request->query('filter')
            : 'all';

        // "Upcoming" = a live booking (confirmed or awaiting payment) for a future showtime.
        $upcomingScope = fn($query) => $query
            ->whereIn('status', ['confirmed', 'pending_payment'])
            ->whereHas('showtime', fn($s) => $s->where('starts_at', '>=', now()));

        // Always scoped to the logged-in customer: nobody sees another user's bookings.
        $bookings = Booking::query()
            ->where('user_id', $userId)
            ->with([
                'showtime.movie',
                'showtime.screen',
                'bookingSeats.seat',
                'hold',
                'payments.refunds',
            ])
            ->when($filter === 'upcoming', $upcomingScope)
            ->when($filter === 'past', fn($q) => $q->whereNot(fn($w) => $upcomingScope($w)))
            ->latest('created_at')
            ->get();

        $upcomingCount = Booking::where('user_id', $userId)->where($upcomingScope)->count();
        $allCount = Booking::where('user_id', $userId)->count();

        $counts = [
            'upcoming' => $upcomingCount,
            'past' => $allCount - $upcomingCount,
            'all' => $allCount,
        ];

        // Next confirmed booking shown as the big card.
        $featured = $bookings
            ->where('status', 'confirmed')
            ->filter(fn($b) => $b->showtime->starts_at->isFuture())
            ->sortBy(fn($b) => $b->showtime->starts_at)
            ->first();

        // Pending payments whose hold is still active.
        $pending = $bookings->filter(fn($b) => $b->status === 'pending_payment'
            && $b->hold
            && $b->hold->status === 'active'
            && $b->hold->expires_at->isFuture());

        return view('bookings.index', compact('bookings', 'counts', 'filter', 'featured', 'pending'));
    }

    public function show(Booking $booking): View
    {
        // Policy: only the owner may view (403 for anyone else).
        Gate::authorize('view', $booking);

        $booking->load([
            'user',
            'showtime.movie',
            'showtime.screen',
            'bookingSeats.seat',
            'hold',
            'payments.refunds',
            'tickets',
        ]);

        // Status / type columns may be cast to enums, so always read the raw value.
        $val = fn($v) => $v instanceof \BackedEnum ? $v->value : (string) $v;
        $money = fn(int $cents) => '$' . number_format($cents / 100, 2);

        $status = $val($booking->status);
        $showtime = $booking->showtime;
        $movie = $showtime->movie;
        $refSuffix = Str::afterLast($booking->booking_reference, '-');

        $statusLabel = [
            'confirmed' => 'Confirmed',
            'pending_payment' => 'Pending payment',
            'failed' => 'Failed',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ][$status] ?? ucfirst(str_replace('_', ' ', $status));

        // ---- Seats -------------------------------------------------------
        $bookingSeats = $booking->bookingSeats
            ->sortBy(fn($bs) => $bs->seat->row_label . str_pad((string) $bs->seat->seat_number, 3, '0', STR_PAD_LEFT))
            ->values();

        $seatNames = $bookingSeats->map(fn($bs) => $bs->seat->row_label . $bs->seat->seat_number);
        $seatSummary = $seatNames->join(' + ');
        $seatSentence = Arr::join($seatNames->all(), ', ', ' and ');

        $types = $bookingSeats->map(fn($bs) => ucfirst($val($bs->seat->type)))->unique();
        $typeLabel = $types->count() === 1 ? $types->first() : 'Mixed seating';

        // ---- Tickets (one per booked seat) -------------------------------
        $tickets = $booking->tickets->keyBy('booking_seat_id');

        $ticketRows = $bookingSeats->map(function ($bs, $i) use ($tickets, $val, $refSuffix) {
            $ticket = $tickets->get($bs->id);
            $ticketStatus = $ticket ? $val($ticket->status) : null;

            return [
                'seat' => $bs->seat->row_label . $bs->seat->seat_number . ' · ' . ucfirst($val($bs->seat->type)),
                'number' => $ticket ? sprintf('T-%s-%02d', $refSuffix, $i + 1) : '—',
                'scan' => match ($ticketStatus) {
                    'used' => 'Scanned' . ($ticket->used_at ? ' · ' . Carbon::parse($ticket->used_at)->format('H:i') : ''),
                    'void' => 'Void',
                    'valid' => 'Not scanned',
                    default => '—',
                },
            ];
        });

        // Scan rule from the project spec: 1 hour before the show to 30 minutes after it starts.
        $entryWindow = $showtime->starts_at->format('M j') . ' · '
            . $showtime->starts_at->copy()->subHour()->format('H:i') . '–'
            . $showtime->starts_at->copy()->addMinutes(30)->format('H:i');

        // ---- Payment -----------------------------------------------------
        $payment = $booking->payments->sortByDesc('created_at')->first();
        $paymentStatus = $payment ? $val($payment->status) : null;

        $paymentLine = match ($paymentStatus) {
            'paid', 'refunded' => 'Verified payment · Stripe test mode  ·  ' . $money($payment->amount_cents) . ' ' . $payment->currency,
            'pending' => 'Awaiting payment · Stripe test mode  ·  ' . $money($payment->amount_cents) . ' ' . $payment->currency,
            'failed' => 'Payment failed · Stripe test mode  ·  ' . $money($payment->amount_cents) . ' ' . $payment->currency,
            default => 'No payment recorded',
        };

        $paidAt = $payment && $payment->paid_at
            ? Carbon::parse($payment->paid_at)->format('M j, Y · H:i')
            : '—';

        $paymentReference = $payment ? 'PAY-' . $refSuffix : '—';

        $refunds = $booking->payments->flatMap(fn($p) => $p->refunds)->sortBy('requested_at')->values();

        $refundLines = $refunds->map(fn($r) => $money($r->amount_cents) . ' · ' . ucfirst($val($r->status)));

        // ---- Cancellation ------------------------------------------------
        // Display only. The real cancel/refund rule is enforced by the cancel Action (Member B).
        $windowHours = \App\Models\Setting::readInt('cancellation_window_hours', 2);

        $deadline = $showtime->starts_at->copy()->subHours($windowHours);
        $anyScanned = $tickets->contains(fn($t) => $val($t->status) === 'used');

        $cancellation = null;
        if ($status === 'confirmed') {
            $eligible = now()->lt($deadline) && !$anyScanned;
            $cancellation = [
                'state' => $eligible ? 'eligible' : 'closed',
                'reason' => $anyScanned ? 'scanned' : 'cutoff',
                'deadline' => $deadline->format('M j \a\t H:i'),
                'hours' => $windowHours,
                'refund' => $money($booking->total_amount_cents),
            ];
        } elseif (in_array($status, ['cancelled', 'refunded'], true)) {
            $cancellation = ['state' => 'cancelled'];
        }

        // ---- Timeline (newest first) -------------------------------------
        $timeline = collect();

        foreach ($refunds->reverse() as $refund) {
            if ($val($refund->status) === 'succeeded' && $refund->completed_at) {
                $timeline->push([Carbon::parse($refund->completed_at), 'Refund of ' . $money($refund->amount_cents) . ' completed']);
            } elseif ($val($refund->status) === 'pending') {
                $timeline->push([Carbon::parse($refund->requested_at), 'Refund of ' . $money($refund->amount_cents) . ' requested']);
            }
        }
        if ($paymentStatus === 'failed') {
            $timeline->push([Carbon::parse($payment->updated_at), 'Payment failed']);
        }
        if ($booking->cancelled_at) {
            $timeline->push([$booking->cancelled_at, 'Booking cancelled']);
        }
        if ($booking->confirmed_at) {
            $timeline->push([$booking->confirmed_at, 'Payment verified, booking confirmed']);
        }
        if ($booking->tickets->isNotEmpty()) {
            $timeline->push([Carbon::parse($booking->tickets->min('created_at')), 'Tickets ' . $seatSentence . ' issued']);
        }
        if ($booking->hold) {
            if ($val($booking->hold->status) === 'expired') {
                $timeline->push([Carbon::parse($booking->hold->expires_at), 'Hold expired']);
            }
            $timeline->push([Carbon::parse($booking->hold->created_at), $seatSentence . ' held together']);
        }
        $timeline->push([$booking->created_at, 'Booking created']);

        $timeline = $timeline->map(fn($e) => [
            'time' => $e[0]->isSameDay($booking->created_at) ? $e[0]->format('H:i') : $e[0]->format('M j · H:i'),
            'text' => $e[1],
        ]);

        // Mail delivery is not tracked in the database yet (Member B's queued emails).
        // Set this to true to preview the notice from the design.
        $emailPending = false;

        return view('bookings.show', [
            'booking' => $booking,
            'movie' => $movie,
            'showtime' => $showtime,
            'status' => $status,
            'statusLabel' => $statusLabel,
            'seatSummary' => $seatSummary,
            'typeLabel' => $typeLabel,
            'total' => $money($booking->total_amount_cents),
            'ticketRows' => $ticketRows,
            'entryWindow' => $entryWindow,
            'paymentLine' => $paymentLine,
            'paidAt' => $paidAt,
            'paymentReference' => $paymentReference,
            'refundLines' => $refundLines,
            'cancellation' => $cancellation,
            'timeline' => $timeline,
            'emailPending' => $emailPending,
        ]);
    }
    public function cancel(
        Booking $booking,
        CancelBookingAction $action,
        Request $request
    ): RedirectResponse {
        Gate::authorize('view', $booking);

        $action->execute($booking, (int) $request->user()->id);

        return redirect()
            ->route('bookings.cancelled', $booking)
            ->with('status', 'Your cancellation has been submitted. Your refund is pending.');
    }
    public function confirmCancel(Booking $booking): View|RedirectResponse
    {
        Gate::authorize('view', $booking);

        $booking->load(['showtime.movie', 'showtime.screen', 'bookingSeats.seat', 'tickets']);

        $showtime = $booking->showtime;
        $windowHours = \App\Models\Setting::readInt('cancellation_window_hours', 2);

        $deadline = $showtime->starts_at->copy()->subHours($windowHours);
        $anyScanned = $booking->tickets->contains(fn($t) => $this->val($t->status) === 'used');

        // Display guard only. The real rule lives in the cancel Action later.
        if ($this->val($booking->status) !== 'confirmed' || $anyScanned || now()->gte($deadline)) {
            return redirect()->route('bookings.show', $booking);
        }

        $seat = $this->seatInfo($booking);
        $n = $seat['count'];

        return view('bookings.cancel', [
            'booking' => $booking,
            'seat' => $seat,
            'seatHeadline' => match (true) {
                $n === 1 => 'This cancels your seat.',
                $n === 2 => 'This cancels both seats.',
                default => "This cancels all {$n} seats.",
            },
            'ticketsPhrase' => match (true) {
                $n === 1 => 'Your ticket will be',
                $n === 2 => 'Both tickets will be',
                default => 'All tickets will be',
            },
            'scanText' => match (true) {
                $n === 1 => 'Ticket not scanned',
                $n === 2 => 'Neither ticket scanned',
                default => 'No tickets scanned',
            },
            'total' => $this->money($booking->total_amount_cents),
            'totalShort' => $this->money($booking->total_amount_cents, trim: true),
            'deadline' => $deadline->format('M j, Y · H:i'),
        ]);
    }

    public function cancelled(Booking $booking): View|RedirectResponse
    {
        Gate::authorize('view', $booking);

        $booking->load(['showtime.movie', 'showtime.screen', 'bookingSeats.seat', 'payments.refunds']);

        if (!in_array($this->val($booking->status), ['cancelled', 'refunded'], true)) {
            return redirect()->route('bookings.show', $booking);
        }

        $seat = $this->seatInfo($booking);
        $refSuffix = Str::afterLast($booking->booking_reference, '-');

        $refund = $booking->payments
            ->flatMap(fn($p) => $p->refunds)
            ->sortByDesc('requested_at')
            ->first();
        $refundState = $refund ? $this->val($refund->status) : 'none'; // pending | succeeded | failed | none

        $events = collect();
        if ($refund) {
            $events->push(match ($refundState) {
                'succeeded' => [Carbon::parse($refund->completed_at ?? $refund->requested_at), 'Full refund completed'],
                'failed' => [Carbon::parse($refund->requested_at), 'Refund failed · cinema team reviewing'],
                default => [Carbon::parse($refund->requested_at), 'Full refund requested · pending'],
            });
        }
        if ($booking->cancelled_at) {
            $events->push([$booking->cancelled_at, 'Tickets voided; ' . $seat['names']->join('/') . ' released']);
            $events->push([$booking->cancelled_at, 'Customer cancelled full booking']);
        }
        if ($booking->confirmed_at) {
            $events->push([$booking->confirmed_at, 'Payment verified · ' . $this->money($booking->total_amount_cents)]);
        }
        $timeline = $events->map(fn($e) => [
            'time' => $e[0]->format('H:i'),
            'text' => $e[1],
        ]);

        return view('bookings.cancelled', [
            'booking' => $booking,
            'seat' => $seat,
            'total' => $this->money($booking->total_amount_cents),
            'refundState' => $refundState,
            'requestedAt' => $refund ? Carbon::parse($refund->requested_at)->format('M j · H:i') : '—',
            'completedAt' => $refund?->completed_at ? Carbon::parse($refund->completed_at)->format('H:i') : null,
            'refundRef' => $refund ? 'RF-' . $refSuffix : '—',
            'timeline' => $timeline,
        ]);
    }

    // ---- small shared helpers ----------------------------------------------
    private function val($v): string
    {
        return $v instanceof \BackedEnum ? $v->value : (string) $v;
    }

    private function money(int $cents, bool $trim = false): string
    {
        return '$' . number_format($cents / 100, $trim && $cents % 100 === 0 ? 0 : 2);
    }

    private function seatInfo(Booking $booking): array
    {
        $names = $booking->bookingSeats
            ->sortBy(fn($bs) => $bs->seat->row_label . str_pad((string) $bs->seat->seat_number, 3, '0', STR_PAD_LEFT))
            ->map(fn($bs) => $bs->seat->row_label . $bs->seat->seat_number)
            ->values();

        $types = $booking->bookingSeats->map(fn($bs) => ucfirst($this->val($bs->seat->type)))->unique();

        return [
            'names' => $names,
            'summary' => $names->join(' + '),
            'sentence' => Arr::join($names->all(), ', ', ' and '),
            'typeLabel' => $types->count() === 1 ? $types->first() : 'Mixed seating',
            'count' => $names->count(),
        ];
    }
}