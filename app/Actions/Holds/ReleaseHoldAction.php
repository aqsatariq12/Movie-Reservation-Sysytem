<?php

namespace App\Actions\Holds;

use App\Models\Booking;
use App\Models\Hold;
use App\Models\ShowtimeSeat;
use Illuminate\Support\Facades\DB;

class ReleaseHoldAction
{
    /**
     * Free the seats of an active hold.
     *
     * @param  string  $reason  'released' (customer pressed the button) or 'expired' (time ran out)
     * @return bool  true if this call released the hold, false if it was already finished
     */
    public function execute(Hold $hold, string $reason = 'released'): bool
    {
        return DB::transaction(function () use ($hold, $reason) {
            // Lock seats first, then the hold: the same order HoldSeatsAction uses, so they never deadlock.
            ShowtimeSeat::query()->where('hold_id', $hold->id)->orderBy('id')->lockForUpdate()->get();

            $locked = Hold::query()->lockForUpdate()->find($hold->id);

            if (!$locked || $locked->status !== 'active') {
                return false; // already released, expired or converted: nothing to do
            }

            ShowtimeSeat::query()
                ->where('hold_id', $locked->id)
                ->where('status', 'held')
                ->update(['status' => 'available', 'hold_id' => null, 'held_until' => null]);

            $locked->update(['status' => $reason]);

            // Only an unpaid booking is closed. A confirmed booking is never touched here.
            $booking = Booking::query()->where('hold_id', $locked->id)->where('status', 'pending_payment');

            if ($reason === 'expired') {
                $booking->update(['status' => 'expired']);
            } else {
                $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            }

            return true;
        });
    }
}
