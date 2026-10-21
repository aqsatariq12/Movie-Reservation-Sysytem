<?php

namespace App\Http\Controllers\Customer;

use App\Enums\SeatType;
use App\Enums\ShowtimeSeatStatus;
use App\Enums\ShowtimeStatus;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Showtime;

class SeatMapController extends Controller
{
    public function show(Showtime $showtime)
    {
        abort_if($showtime->status !== ShowtimeStatus::Scheduled, 404);

        $now = now();

        $cutoff = Setting::readInt('sales_cutoff_minutes', 15);
        $max = Setting::readInt('max_seats_per_booking', 6);
        $holdMinutes = Setting::readInt('hold_minutes', 10);
        $surcharge = Setting::readInt('premium_surcharge_cents', 200);

        $showtime->load(['movie.genres', 'screen']);

        $movie = $showtime->movie;
        $screen = $showtime->screen;

        abort_unless($movie && $screen, 404);

        $genres = $movie->genres->pluck('name');

        $upcoming = Showtime::query()
            ->where('movie_id', $showtime->movie_id)
            ->where('status', ShowtimeStatus::Scheduled->value)
            ->where('starts_at', '>=', $now->copy()->startOfDay())
            ->orderBy('starts_at')
            ->get();

        $days = $upcoming
            ->groupBy(fn ($item) => $item->starts_at->toDateString())
            ->take(7);

        $sameDay = $upcoming
            ->filter(fn ($item) => $item->starts_at->isSameDay($showtime->starts_at))
            ->values();

        // Load the state of every seat for this specific showtime.
        $states = $showtime->showtimeSeats()
            ->get()
            ->keyBy('seat_id');

        $rows = $screen->seats()
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get()
            ->groupBy('row_label')
            ->map(fn ($seats) => $seats->map(
                function ($seat) use ($states, $now) {
                    $row = $states->get($seat->id);

                    $state = match (true) {
                        $seat->is_disabled => 'disabled',
                        ! $row => 'disabled',
                        $row->status === ShowtimeSeatStatus::Sold => 'sold',
                        $row->status === ShowtimeSeatStatus::Held
                            && $row->held_until
                            && $row->held_until->gt($now) => 'held',
                        default => 'available',
                    };

                    return [
                        'id' => $seat->id,
                        'label' => $seat->row_label . $seat->seat_number,
                        'number' => $seat->seat_number,
                        'premium' => $seat->type === SeatType::Premium,
                        'state' => $state,
                        'price' => $row?->price_cents,
                    ];
                }
            ));

        $available = $rows
            ->flatten(1)
            ->where('state', 'available')
            ->count();

        if (auth()->guest()) {
            session(['url.intended' => url()->current()]);
        }

        return view('customer.seat-map', [
            'showtime' => $showtime,
            'screen' => $screen,
            'movie' => $movie,
            'genres' => $genres,
            'days' => $days,
            'sameDay' => $sameDay,
            'rows' => $rows,
            'available' => $available,
            'max' => $max,
            'holdMinutes' => $holdMinutes,
            'cutoff' => $cutoff,
            'closed' => $now->gte(
                $showtime->starts_at->copy()->subMinutes($cutoff)
            ),
            'now' => $now,
            'surcharge' => $surcharge,
        ]);
    }
}
