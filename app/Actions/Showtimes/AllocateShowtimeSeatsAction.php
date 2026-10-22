<?php

namespace App\Actions\Showtimes;

use App\Models\Screen;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;

class AllocateShowtimeSeatsAction
{
    /** One showtime_seats row per enabled seat; premium seats get the surcharge. */
    public function execute(Showtime $showtime, Screen $screen, int $basePriceCents, int $surchargeCents): int
    {
        $now = now();

        $rows = $screen->seats()->where('is_disabled', false)->get(['id', 'type'])
            ->map(fn($seat) => [
                'showtime_id' => $showtime->id,
                'seat_id' => $seat->id,
                'status' => 'available',
                'price_cents' => $basePriceCents + ($seat->type === 'premium' ? $surchargeCents : 0),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        ShowtimeSeat::query()->insert($rows);

        return count($rows);
    }
}