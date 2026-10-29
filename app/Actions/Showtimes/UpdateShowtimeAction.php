<?php

namespace App\Actions\Showtimes;

use App\Models\Screen;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateShowtimeAction
{
    public function __construct(
        private EnsureNoOverlapAction $noOverlap,
        private AllocateShowtimeSeatsAction $allocate,
    ) {
    }

    public function execute(Showtime $showtime, ?int $movieId, ?int $screenId, ?CarbonInterface $startsAt, int $basePriceCents): Showtime
    {
        $surcharge = Showtime::premiumSurchargeCents();

        return DB::transaction(function () use ($showtime, $movieId, $screenId, $startsAt, $basePriceCents, $surcharge) {
            $show = Showtime::query()->lockForUpdate()->findOrFail($showtime->id);

           if ($show->status->value !== 'scheduled' || $show->starts_at->isPast()) {
                throw ValidationException::withMessages([
                    'showtime_error' => 'Past or cancelled showtimes are read-only.',
                ]);
            }

            // Lock the seat rows too: a hold being made right now is either seen here or waits for us.
            $now = now();
            $seatRows = ShowtimeSeat::query()->where('showtime_id', $show->id)->orderBy('id')->lockForUpdate()->get();

            $locked = $show->hasActiveBookings() || $seatRows->contains(
                fn($row) => $row->status === 'sold'
                    || ($row->status === 'held' && $row->held_until && $row->held_until->gt($now))
            );

            // Booked show (or only the price was sent): movie, screen and start time never change.
            if ($locked || $movieId === null || $screenId === null || $startsAt === null) {
                $show->update(['base_price_cents' => $basePriceCents]);
                $this->reprice($show, $basePriceCents, $surcharge);

                return $show;
            }

            $duration = (int) DB::table('movies')->where('id', $movieId)->value('duration_minutes');
            $endsAt = $startsAt->copy()->addMinutes($duration + Showtime::TURNAROUND_MINUTES);

            $screen = Screen::query()->lockForUpdate()->findOrFail($screenId);
            $this->noOverlap->execute($screen, $startsAt, $endsAt, $show->id);

            $screenChanged = $screen->id !== $show->screen_id;

            $show->update([
                'movie_id' => $movieId,
                'screen_id' => $screen->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'base_price_cents' => $basePriceCents,
            ]);

            if ($screenChanged) {
                // A different screen has different seats: nobody holds anything here, so rebuild them.
                ShowtimeSeat::query()->where('showtime_id', $show->id)->delete();
                $this->allocate->execute($show, $screen, $basePriceCents, $surcharge);
            } else {
                $this->reprice($show, $basePriceCents, $surcharge);
            }

            return $show;
        });
    }

    /** Only seats that are still available get the new price. Held and sold seats keep theirs. */
    private function reprice(Showtime $show, int $basePriceCents, int $surchargeCents): void
    {
        $premiumSeatIds = Seat::query()
            ->where('screen_id', $show->screen_id)
            ->where('type', 'premium')
            ->pluck('id');

        $available = fn() => ShowtimeSeat::query()
            ->where('showtime_id', $show->id)
            ->where('status', 'available');

        $available()->whereIn('seat_id', $premiumSeatIds)->update(['price_cents' => $basePriceCents + $surchargeCents]);
        $available()->whereNotIn('seat_id', $premiumSeatIds)->update(['price_cents' => $basePriceCents]);
    }
}