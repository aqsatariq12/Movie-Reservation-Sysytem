<?php

namespace App\Actions\Showtimes;

use App\Models\Screen;
use App\Models\Showtime;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CreateShowtimeAction
{
    public function __construct(
        private EnsureNoOverlapAction $noOverlap,
        private AllocateShowtimeSeatsAction $allocate,
    ) {
    }

    public function execute(int $movieId, int $screenId, CarbonInterface $startsAt, int $basePriceCents): Showtime
    {
        $duration = (int) DB::table('movies')->where('id', $movieId)->value('duration_minutes');
        $endsAt = $startsAt->copy()->addMinutes($duration + Showtime::TURNAROUND_MINUTES);
        $surcharge = Showtime::premiumSurchargeCents();

        return DB::transaction(function () use ($movieId, $screenId, $startsAt, $endsAt, $basePriceCents, $surcharge) {
            // Lock the screen row so two admins cannot schedule overlapping shows at the same time.
            $screen = Screen::query()->lockForUpdate()->findOrFail($screenId);

            $this->noOverlap->execute($screen, $startsAt, $endsAt);

            $showtime = Showtime::create([
                'movie_id' => $movieId,
                'screen_id' => $screen->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'base_price_cents' => $basePriceCents,
                'status' => 'scheduled',
            ]);

            $this->allocate->execute($showtime, $screen, $basePriceCents, $surcharge);

            return $showtime;
        });
    }
}