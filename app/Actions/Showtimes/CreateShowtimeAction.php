<?php

namespace App\Actions\Showtimes;

use App\Models\Screen;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateShowtimeAction
{
    public function execute(int $movieId, int $screenId, CarbonInterface $startsAt, int $basePriceCents): Showtime
    {
        $duration = (int) DB::table('movies')->where('id', $movieId)->value('duration_minutes');
        $endsAt = $startsAt->copy()->addMinutes($duration + Showtime::TURNAROUND_MINUTES);
        $surcharge = Showtime::premiumSurchargeCents();

        return DB::transaction(function () use ($movieId, $screenId, $startsAt, $endsAt, $basePriceCents, $surcharge) {
            // Lock the screen row so two admins cannot schedule overlapping shows at the same time.
            $screen = Screen::query()->lockForUpdate()->findOrFail($screenId);

            $clash = Showtime::query()
                ->where('screen_id', $screen->id)
                ->where('status', '!=', 'cancelled')
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->orderBy('starts_at')
                ->first();

            if ($clash) {
                $title = DB::table('movies')->where('id', $clash->movie_id)->value('title');
                $window = $clash->starts_at->format('M j, H:i') . '–' . $clash->ends_at->format('H:i');

                throw ValidationException::withMessages([
                    'start_time' => "Overlaps {$title}, {$screen->name}, {$window}. Choose {$clash->ends_at->format('H:i')} or later, or another screen.",
                    'showtime_error' => 'A screen can’t host overlapping movies or overlap the ' . Showtime::TURNAROUND_MINUTES . '-minute turnaround buffer.',
                ]);
            }

            $showtime = Showtime::create([
                'movie_id' => $movieId,
                'screen_id' => $screen->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'base_price_cents' => $basePriceCents,
                'status' => 'scheduled',
            ]);

            // One showtime_seats row for every seat that is not disabled.
            $now = now();
            $rows = $screen->seats()->where('is_disabled', false)->get(['id', 'type'])
                ->map(fn ($seat) => [
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'status' => 'available',
                    'price_cents' => $basePriceCents + ($seat->type === 'premium' ? $surcharge : 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            ShowtimeSeat::query()->insert($rows);

            return $showtime;
        });
    }
}