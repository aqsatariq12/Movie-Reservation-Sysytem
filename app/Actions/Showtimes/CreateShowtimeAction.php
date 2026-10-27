<?php

namespace App\Actions\Showtimes;

use App\Models\AuditLog;
use App\Models\Screen;
use App\Models\Showtime;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CreateShowtimeAction
{
    public function __construct(
        private EnsureNoOverlapAction $noOverlap,
        private AllocateShowtimeSeatsAction $allocate,
    ) {
    }

    public function execute(
        int $movieId,
        int $screenId,
        CarbonInterface $startsAt,
        int $basePriceCents,
        ?User $actor = null,
        ?string $ip = null,
    ): Showtime {
        $duration = (int) DB::table('movies')->where('id', $movieId)->value('duration_minutes');
        $endsAt = $startsAt->copy()->addMinutes($duration + Showtime::TURNAROUND_MINUTES);
        $surcharge = Showtime::premiumSurchargeCents();

        return DB::transaction(function () use ($movieId, $screenId, $startsAt, $endsAt, $basePriceCents, $surcharge, $actor, $ip) {
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

            // Same transaction: a showtime can never exist without its audit entry.
            if ($actor) {
                AuditLog::create([
                    'user_id' => $actor->id,
                    'action' => 'showtime.created',
                    'entity_type' => 'showtime',
                    'entity_id' => $showtime->id,
                    'before_data' => null,
                    'after_data' => [
                        'movie_id' => $movieId,
                        'screen_id' => $screen->id,
                        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                        'base_price_cents' => $basePriceCents,
                    ],
                    'ip_address' => $ip,
                ]);
            }

            return $showtime;
        });
    }
}