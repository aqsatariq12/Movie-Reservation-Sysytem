<?php

namespace App\Actions\Holds;

use App\Models\Booking;
use App\Models\Hold;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HoldSeatsAction
{
    /**
     * Hold the given seats for a customer, all or nothing, and create the pending booking.
     *
     * @param  array<int, int>  $seatIds  physical seat ids (seats.id)
     */
    public function execute(User $user, Showtime $showtime, array $seatIds): Booking
    {
        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));

        $max = Showtime::setting('max_seats_per_booking', 6);
        $holdMinutes = Showtime::setting('hold_minutes', 10);
        $cutoffMinutes = Showtime::setting('sales_cutoff_minutes', 15);

        if (count($seatIds) < 1 || count($seatIds) > $max) {
            $this->fail("Select between 1 and {$max} seats.");
        }

        return DB::transaction(function () use ($user, $showtime, $seatIds, $holdMinutes, $cutoffMinutes) {
            $now = now();

            $show = Showtime::query()->findOrFail($showtime->id);

            if ($show->status !== 'scheduled') {
                $this->fail('This showtime is not on sale.');
            }

            if ($now->gte($show->starts_at->copy()->subMinutes($cutoffMinutes))) {
                $this->fail('Sales for this showtime have closed.');
            }

            $alreadyHolding = Hold::query()
                ->where('user_id', $user->id)
                ->where('showtime_id', $show->id)
                ->where('status', 'active')
                ->where('expires_at', '>', $now)
                ->exists();

            if ($alreadyHolding) {
                $this->fail('You already have an active hold for this showtime. Release it first.');
            }

            // Lock the requested rows, always in id order so two users can never deadlock each other.
            $rows = ShowtimeSeat::query()
                ->where('showtime_id', $show->id)
                ->whereIn('seat_id', $seatIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($rows->count() !== count($seatIds)) {
                $this->fail('One or more of the selected seats do not exist for this showtime.');
            }

            // Re-check AFTER the lock: a seat is free if it is available, or its hold has already expired.
            $taken = $rows->filter(function (ShowtimeSeat $row) use ($now) {
                if ($row->status === 'available') {
                    return false;
                }

                return ! ($row->status === 'held' && $row->held_until !== null && $row->held_until->lte($now));
            });

            if ($taken->isNotEmpty()) {
                $this->fail('Some of the seats you chose were just taken. No seats were held.');
            }

            // Old, expired holds on these seats are closed so they cannot be paid for later by mistake.
            $oldHoldIds = $rows->pluck('hold_id')->filter()->unique()->all();
            if ($oldHoldIds) {
                Hold::query()->whereIn('id', $oldHoldIds)->where('status', 'active')->update(['status' => 'expired']);
                Booking::query()->whereIn('hold_id', $oldHoldIds)->where('status', 'pending_payment')->update(['status' => 'expired']);
            }

            $hold = Hold::create([
                'user_id' => $user->id,
                'showtime_id' => $show->id,
                'status' => 'active',
                'expires_at' => $now->copy()->addMinutes($holdMinutes),
            ]);

            ShowtimeSeat::query()
                ->whereIn('id', $rows->pluck('id'))
                ->update([
                    'status' => 'held',
                    'hold_id' => $hold->id,
                    'booking_id' => null,
                    'held_until' => $hold->expires_at,
                ]);

            // The price comes from the server (showtime_seats.price_cents), never from the browser.
            $booking = Booking::create([
                'booking_reference' => $this->newReference($show),
                'user_id' => $user->id,
                'showtime_id' => $show->id,
                'hold_id' => $hold->id,
                'status' => 'pending_payment',
                'total_amount_cents' => (int) $rows->sum('price_cents'),
                'currency' => 'USD',
            ]);

            $booking->bookingSeats()->createMany(
                $rows->map(fn (ShowtimeSeat $row) => [
                    'seat_id' => $row->seat_id,
                    'price_cents' => $row->price_cents,
                ])->all()
            );

            return $booking->load('bookingSeats');
        });
    }

    private function newReference(Showtime $showtime): string
    {
        do {
            $reference = 'CB-' . $showtime->starts_at->format('ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (Booking::where('booking_reference', $reference)->exists());

        return $reference;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['seats' => $message]);
    }
}