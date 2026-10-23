<?php

namespace App\Actions\Showtimes;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\ShowtimeSeatStatus;
use App\Enums\ShowtimeStatus;
use App\Enums\TicketStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Hold;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelShowtimeAction
{
    /** Numbers shown on the confirmation screen (read-only). */
    public function impact(Showtime $showtime): array
    {
        $paid = Booking::query()
            ->where('showtime_id', $showtime->id)
            ->where('status', BookingStatus::Confirmed->value);

        $paidIds = (clone $paid)->pluck('id');

        $activeHolds = Hold::query()
            ->where('showtime_id', $showtime->id)
            ->where('status', HoldStatus::Active->value)
            ->where('expires_at', '>', now());

        return [
            'paid_bookings' => $paidIds->count(),
            'refund_cents' => (int) (clone $paid)->sum('total_amount_cents'),
            'tickets' => Ticket::query()
                ->whereIn('booking_id', $paidIds)
                ->where('status', TicketStatus::Valid->value)
                ->count(),
            'holds' => (clone $activeHolds)->count(),
            'held_seats' => ShowtimeSeat::query()
                ->whereIn('hold_id', (clone $activeHolds)->select('id'))
                ->where('status', ShowtimeSeatStatus::Held->value)
                ->count(),
        ];
    }

    public function execute(Showtime $showtime, User $actor, string $reason, ?string $ip = null): Showtime
    {
        return DB::transaction(function () use ($showtime, $actor, $reason, $ip) {
            // Same lock order as UpdateShowtimeAction: showtime first, then seats.
            $show = Showtime::query()->lockForUpdate()->findOrFail($showtime->id);

            if ($show->status !== ShowtimeStatus::Scheduled || $show->starts_at->isPast()) {
                throw ValidationException::withMessages([
                    'showtime_error' => 'Only a scheduled show that has not started can be cancelled.',
                ]);
            }

            ShowtimeSeat::query()
                ->where('showtime_id', $show->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $bookings = Booking::query()
                ->where('showtime_id', $show->id)
                ->whereIn('status', [
                    BookingStatus::Confirmed->value,
                    BookingStatus::PendingPayment->value,
                ])
                ->lockForUpdate()
                ->get();

            $confirmed = $bookings->filter(fn ($b) => $b->status === BookingStatus::Confirmed);
            $confirmedIds = $confirmed->pluck('id');

            // 1. Void tickets of paid bookings.
            $voided = Ticket::query()
                ->whereIn('booking_id', $confirmedIds)
                ->where('status', TicketStatus::Valid->value)
                ->update(['status' => TicketStatus::Void->value]);

            // 2. Cancel every live booking (paid and pending payment).
            Booking::query()
                ->whereIn('id', $bookings->pluck('id'))
                ->update([
                    'status' => BookingStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ]);

            // 3. Close active holds.
            Hold::query()
                ->where('showtime_id', $show->id)
                ->where('status', HoldStatus::Active->value)
                ->update(['status' => HoldStatus::Released->value]);

            // 4. Release every seat of this show.
            ShowtimeSeat::query()
                ->where('showtime_id', $show->id)
                ->where('status', '!=', ShowtimeSeatStatus::Available->value)
                ->update([
                    'status' => ShowtimeSeatStatus::Available->value,
                    'hold_id' => null,
                    'booking_id' => null,
                    'held_until' => null,
                ]);

            // 5. Cancel the showtime itself.
            $show->update(['status' => ShowtimeStatus::Cancelled]);

            // TODO (refund member): create ONE full refund request for each booking in
            // $confirmed (skip any booking that already has a refund, to avoid duplicates),
            // then queue the "showtime cancelled" email. Neither may change the state above.

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'showtime.cancelled',
                'entity_type' => 'showtime',
                'entity_id' => $show->id,
                'before_data' => ['status' => ShowtimeStatus::Scheduled->value],
                'after_data' => [
                    'status' => ShowtimeStatus::Cancelled->value,
                    'reason' => $reason,
                    'paid_bookings_cancelled' => $confirmed->count(),
                    'pending_bookings_cancelled' => $bookings->count() - $confirmed->count(),
                    'tickets_voided' => $voided,
                    'refund_total_cents' => (int) $confirmed->sum('total_amount_cents'),
                ],
                'ip_address' => $ip,
            ]);

            return $show;
        });
    }
}