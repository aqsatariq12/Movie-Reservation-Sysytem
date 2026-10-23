<?php

namespace App\Actions\Holds;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\ShowtimeSeatStatus;
use App\Enums\ShowtimeStatus;
use App\Models\Booking;
use App\Models\Hold;
use App\Models\Setting;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Enums\SeatType;
use App\Models\Seat;

class HoldSeatsAction
{
    /**
     * Hold seats atomically and create a pending-payment booking.
     *
     * @param array<int, int> $seatIds Physical seat IDs.
     */
    public function execute(
        User $user,
        Showtime $showtime,
        array $seatIds
    ): Booking {
        $seatIds = array_values(
            array_unique(array_map('intval', $seatIds))
        );

        $max = Setting::readInt('max_seats_per_booking', 6);
        $holdMinutes = Setting::readInt('hold_minutes', 10);
        $cutoffMinutes = Setting::readInt('sales_cutoff_minutes', 15);

        if (count($seatIds) < 1 || count($seatIds) > $max) {
            $this->fail("Select between 1 and {$max} seats.");
        }

        return DB::transaction(function () use ($user, $showtime, $seatIds, $holdMinutes, $cutoffMinutes) {
            $now = now();

            $show = Showtime::query()->lockForUpdate()->findOrFail($showtime->id);

            if ($show->status !== ShowtimeStatus::Scheduled) {
                $this->fail('This showtime is not on sale.');
            }

            if (
                $now->gte(
                    $show->starts_at->copy()->subMinutes($cutoffMinutes)
                )
            ) {
                $this->fail('Sales for this showtime have closed.');
            }

            $alreadyHolding = Hold::query()
                ->where('user_id', $user->id)
                ->where('showtime_id', $show->id)
                ->where('status', HoldStatus::Active->value)
                ->where('expires_at', '>', $now)
                ->exists();

            if ($alreadyHolding) {
                $this->fail(
                    'You already have an active hold for this showtime. Release it first.'
                );
            }

            // Lock seats in a consistent order to reduce deadlock risk.
            $rows = ShowtimeSeat::query()
                ->where('showtime_id', $show->id)
                ->whereIn('seat_id', $seatIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($rows->count() !== count($seatIds)) {
                $this->fail(
                    'One or more selected seats do not exist for this showtime.'
                );
            }

            // Available seats and seats with expired holds may be selected.
            $taken = $rows->filter(function (ShowtimeSeat $row) use ($now) {
                if ($row->status === ShowtimeSeatStatus::Available) {
                    return false;
                }

                return !(
                    $row->status === ShowtimeSeatStatus::Held
                    && $row->held_until !== null
                    && $row->held_until->lte($now)
                );
            });

            if ($taken->isNotEmpty()) {
                $this->fail(
                    'Some selected seats were just taken. No seats were held.'
                );
            }
            // Always price from the showtime's current base price, never from a stale seat row.
            $surcharge = Setting::readInt('premium_surcharge_cents', 200);
            $premiumIds = Seat::query()
                ->whereIn('id', $seatIds)
                ->where('type', SeatType::Premium->value)
                ->pluck('id')
                ->all();

            foreach ($rows as $row) {
                $row->price_cents = $show->base_price_cents
                    + (in_array($row->seat_id, $premiumIds, true) ? $surcharge : 0);
                $row->save();
            }

            // Close old holds attached to seats whose hold time has expired.
            $oldHoldIds = $rows
                ->pluck('hold_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($oldHoldIds !== []) {
                Hold::query()
                    ->whereIn('id', $oldHoldIds)
                    ->where('status', HoldStatus::Active->value)
                    ->update([
                        'status' => HoldStatus::Expired->value,
                    ]);

                Booking::query()
                    ->whereIn('hold_id', $oldHoldIds)
                    ->where('status', BookingStatus::PendingPayment->value)
                    ->update([
                        'status' => BookingStatus::Expired->value,
                    ]);
            }

            $hold = Hold::create([
                'user_id' => $user->id,
                'showtime_id' => $show->id,
                'status' => HoldStatus::Active,
                'expires_at' => $now->copy()->addMinutes($holdMinutes),
            ]);

            ShowtimeSeat::query()
                ->whereIn('id', $rows->pluck('id'))
                ->update([
                    'status' => ShowtimeSeatStatus::Held->value,
                    'hold_id' => $hold->id,
                    'booking_id' => null,
                    'held_until' => $hold->expires_at,
                ]);

            $booking = Booking::create([
                'booking_reference' => $this->newReference($show),
                'user_id' => $user->id,
                'showtime_id' => $show->id,
                'hold_id' => $hold->id,
                'status' => BookingStatus::PendingPayment,
                'total_amount_cents' => (int) $rows->sum('price_cents'),
                'currency' => 'USD',
            ]);

            $booking->bookingSeats()->createMany(
                $rows->map(fn(ShowtimeSeat $row) => [
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
            $reference = 'CB-'
                . $showtime->starts_at->format('ymd')
                . '-'
                . str_pad(
                    (string) random_int(0, 9999),
                    4,
                    '0',
                    STR_PAD_LEFT
                );
        } while (
            Booking::where('booking_reference', $reference)->exists()
        );

        return $reference;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'seats' => $message,
        ]);
    }
}
