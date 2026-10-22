<?php

use App\Actions\Screens\GenerateSeatsAction;
use App\Actions\Showtimes\CreateShowtimeAction;
use App\Models\Screen;
use App\Models\Showtime;
use Illuminate\Support\Facades\DB;

if (! function_exists('makeShowtime')) {
    /**
     * A showtime tomorrow evening on a small 3 x 4 screen, built with the real Actions.
     *
     * @return array{0: Showtime, 1: Screen}
     */
    function makeShowtime(): array
    {
        $movieId = DB::table('movies')->insertGetId([
            'title' => 'Test Movie',
            'slug' => 'test-movie',
            'synopsis' => 'A movie used only by the automated tests.',
            'duration_minutes' => 100,
            'status' => 'now_showing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $screen = Screen::create(['name' => 'Test Screen', 'total_rows' => 3, 'seats_per_row' => 4]);
        app(GenerateSeatsAction::class)->execute($screen);

        $showtime = app(CreateShowtimeAction::class)
            ->execute($movieId, $screen->id, now()->addDay()->setTime(19, 30), 1000);

        return [$showtime, $screen];
    }
}

if (! function_exists('seatIds')) {
    /** @return array<int, int> */
    function seatIds(Screen $screen, int $count): array
    {
        return $screen->seats()->orderBy('id')->limit($count)->pluck('id')->all();
    }
}