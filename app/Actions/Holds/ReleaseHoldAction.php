<?php

namespace App\Actions\Holds;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\ShowtimeSeatStatus;
use App\Models\Booking;
use App\Models\Hold;
use App\Models\ShowtimeSeat;
use Illuminate\Support\Facades\DB;

class ReleaseHoldAction
{
    /**
     * Release an active hold and free only seats still held by it.
     */
    public function execute(Hold $hold, string $reason = 'released'): bool
    {
        if (! in_array($reason, ['released', 'expired'], true)) {
            throw new \InvalidArgumentException('Invalid hold release reason.');
        }

        return DB::transaction(function () use ($hold, $reason) {
            // Lock seats first, in the same order as HoldSeatsAction.
            ShowtimeSeat::query()
                ->where('hold_id', $hold->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $locked = Hold::query()
                ->lockForUpdate()
                ->find($hold->id);

            if (! $locked || $locked->status !== HoldStatus::Active) {
                return false;
            }

            // Free only seats still held by this hold. Never free sold seats.
            ShowtimeSeat::query()
                ->where('hold_id', $locked->id)
                ->where('status', ShowtimeSeatStatus::Held->value)
                ->update([
                    'status' => ShowtimeSeatStatus::Available->value,
                    'hold_id' => null,
                    'booking_id' => null,
                    'held_until' => null,
                ]);

            $locked->update([
                'status' => $reason === 'expired'
                    ? HoldStatus::Expired
                    : HoldStatus::Released,
            ]);

            // Only close a booking that is still awaiting payment.
            $bookingQuery = Booking::query()
                ->where('hold_id', $locked->id)
                ->where('status', BookingStatus::PendingPayment->value);

            if ($reason === 'expired') {
                $bookingQuery->update([
                    'status' => BookingStatus::Expired->value,
                ]);
            } else {
                $bookingQuery->update([
                    'status' => BookingStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ]);
            }

            return true;
        });
    }
}
