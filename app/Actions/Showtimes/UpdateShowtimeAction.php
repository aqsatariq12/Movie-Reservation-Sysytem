<?php

namespace App\Actions\Showtimes;

use App\Enums\ShowtimeStatus;
use App\Models\AuditLog;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateShowtimeAction
{
    public function __construct(
        private EnsureNoOverlapAction $noOverlap,
        private AllocateShowtimeSeatsAction $allocate,
    ) {
    }

    public function execute(
        Showtime $showtime,
        ?int $movieId,
        ?int $screenId,
        ?CarbonInterface $startsAt,
        int $basePriceCents,
        ?User $actor = null,
        ?string $ip = null,
    ): Showtime {
        $surcharge = Showtime::premiumSurchargeCents();

        return DB::transaction(function () use ($showtime, $movieId, $screenId, $startsAt, $basePriceCents, $surcharge, $actor, $ip) {
            $show = Showtime::query()->lockForUpdate()->findOrFail($showtime->id);

            if ($show->status !== ShowtimeStatus::Scheduled || $show->starts_at->isPast()) {
                throw ValidationException::withMessages([
                    'showtime_error' => 'Past or cancelled showtimes are read-only.',
                ]);
            }

            // Remember how it looked before any change, for the audit entry.
            $before = $this->snapshot($show);

            // Lock the seat rows too: a hold being made right now is either seen here or waits for us.
            $now = now();
            $seatRows = ShowtimeSeat::query()->where('showtime_id', $show->id)->orderBy('id')->lockForUpdate()->get();

            $locked = $show->hasActiveBookings() || $seatRows->contains(
                fn ($row) => $row->status === 'sold'
                    || ($row->status === 'held' && $row->held_until && $row->held_until->gt($now))
            );

            // Booked show (or only the price was sent): movie, screen and start time never change.
            if ($locked || $movieId === null || $screenId === null || $startsAt === null) {
                $show->update(['base_price_cents' => $basePriceCents]);
                $this->reprice($show, $basePriceCents, $surcharge);
                $this->audit($show, $before, $actor, $ip);

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

            $this->audit($show, $before, $actor, $ip);

            return $show;
        });
    }

    /** The fields an admin can change on a showtime. */
    private function snapshot(Showtime $show): array
    {
        return [
            'movie_id' => $show->movie_id,
            'screen_id' => $show->screen_id,
            'starts_at' => $show->starts_at->format('Y-m-d H:i:s'),
            'base_price_cents' => $show->base_price_cents,
        ];
    }

    /** Writes one audit entry holding only the fields that changed. */
    private function audit(Showtime $show, array $before, ?User $actor, ?string $ip): void
    {
        if (! $actor) {
            return;
        }

        $after = $this->snapshot($show);
        $changed = array_keys(array_diff_assoc($after, $before));

        if ($changed === []) {
            return; // saved without changing anything
        }

        AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'showtime.updated',
            'entity_type' => 'showtime',
            'entity_id' => $show->id,
            'before_data' => Arr::only($before, $changed),
            'after_data' => Arr::only($after, $changed),
            'ip_address' => $ip,
        ]);
    }

    /** Only seats that are still available get the new price. Held and sold seats keep theirs. */
    private function reprice(Showtime $show, int $basePriceCents, int $surchargeCents): void
    {
        $premiumSeatIds = Seat::query()
            ->where('screen_id', $show->screen_id)
            ->where('type', 'premium')
            ->pluck('id');

        $available = fn () => ShowtimeSeat::query()
            ->where('showtime_id', $show->id)
            ->where('status', 'available');

        $available()->whereIn('seat_id', $premiumSeatIds)->update(['price_cents' => $basePriceCents + $surchargeCents]);
        $available()->whereNotIn('seat_id', $premiumSeatIds)->update(['price_cents' => $basePriceCents]);
    }
}