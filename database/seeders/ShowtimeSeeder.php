<?php

namespace Database\Seeders;

use App\Enums\ShowtimeStatus;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Showtime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ShowtimeSeeder extends Seeder
{
    /**
     * Seven days of showtimes starting today, so the home page always has data.
     *
     * Every slot on a screen is at least 150 minutes after the previous one and
     * the longest "now showing" film is 124 minutes, so showtimes on the same
     * screen can never overlap.
     */
    public function run(): void
    {
        $movies = Movie::nowShowing()->orderBy('id')->get();

        if ($movies->isEmpty()) {
            return;
        }

        $schedule = [
            'Screen 1' => ['times' => ['14:30', '17:00', '19:30', '22:00'], 'base_price_cents' => 1000],
            'Screen 2 IMAX' => ['times' => ['15:00', '18:00', '20:30'], 'base_price_cents' => 1400],
        ];

        $screenNumber = 0;

        foreach ($schedule as $screenName => $config) {
            $screen = Screen::where('name', $screenName)->first();

            if (! $screen) {
                continue;
            }

            foreach (range(0, 6) as $dayOffset) {
                $date = Carbon::today()->addDays($dayOffset);

                foreach ($config['times'] as $slot => $time) {
                    // Rotate films across slots, days and screens so the schedule looks varied.
                    $movie = $movies[($slot + $dayOffset + $screenNumber * 2) % $movies->count()];

                    $startsAt = $date->copy()->setTimeFromTimeString($time);

                    Showtime::firstOrCreate(
                        ['screen_id' => $screen->id, 'starts_at' => $startsAt],
                        [
                            'movie_id' => $movie->id,
                            'ends_at' => $startsAt->copy()->addMinutes($movie->duration_minutes),
                            'base_price_cents' => $config['base_price_cents'],
                            'status' => ShowtimeStatus::Scheduled,
                        ],
                    );
                }
            }

            $screenNumber++;
        }
    }
}
