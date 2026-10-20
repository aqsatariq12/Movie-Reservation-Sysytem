<?php

namespace App\Actions\Showtimes;

use App\Models\Screen;
use App\Models\Showtime;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnsureNoOverlapAction
{
    /**
     * Throws a validation error if another (not cancelled) showtime on this screen overlaps
     * the given window. ends_at already includes the turnaround buffer.
     */
    public function execute(Screen $screen, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $ignoreShowtimeId = null): void
    {
        $clash = Showtime::query()
            ->where('screen_id', $screen->id)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreShowtimeId, fn($q) => $q->where('id', '!=', $ignoreShowtimeId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->orderBy('starts_at')
            ->first();

        if (!$clash) {
            return;
        }

        $title = DB::table('movies')->where('id', $clash->movie_id)->value('title');
        $window = $clash->starts_at->format('M j, H:i') . '–' . $clash->ends_at->format('H:i');

        throw ValidationException::withMessages([
            'start_time' => "Overlaps {$title}, {$screen->name}, {$window}. Choose {$clash->ends_at->format('H:i')} or later, or another screen.",
            'showtime_error' => 'A screen can’t host overlapping movies or overlap the ' . Showtime::TURNAROUND_MINUTES . '-minute turnaround buffer.',
        ]);
    }
}