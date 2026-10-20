<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use Illuminate\Support\Facades\DB;

class SeatMapController extends Controller
{
    public function show(Showtime $showtime)
    {
        abort_if($showtime->status !== 'scheduled', 404);

        $now = now();
        $cutoff = Showtime::setting('sales_cutoff_minutes', 15);
        $screen = $showtime->screen;

        // TODO: use the Movie model once the owner's branch is merged
        $movie = DB::table('movies')->where('id', $showtime->movie_id)->first();
        $genres = DB::table('genres')
            ->join('genre_movie', 'genres.id', '=', 'genre_movie.genre_id')
            ->where('genre_movie.movie_id', $movie->id)
            ->orderBy('genres.name')
            ->pluck('genres.name');

        $upcoming = Showtime::query()
            ->where('movie_id', $showtime->movie_id)
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', $now->copy()->startOfDay())
            ->orderBy('starts_at')
            ->get();

        $days = $upcoming->groupBy(fn($s) => $s->starts_at->toDateString())->take(7);
        $sameDay = $upcoming->filter(fn($s) => $s->starts_at->isSameDay($showtime->starts_at))->values();

        // One entry per physical seat, with its state for THIS showtime.
        $states = $showtime->showtimeSeats()->get()->keyBy('seat_id');

        $rows = $screen->seats()
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get()
            ->groupBy('row_label')
            ->map(fn($seats) => $seats->map(function ($seat) use ($states, $now) {
                $row = $states->get($seat->id);
                $state = 'disabled'; // disabled seats have no showtime_seats row
    
                if ($row) {
                    $state = match (true) {
                        $row->status === 'sold' => 'sold',
                        $row->status === 'held' && $row->held_until && $row->held_until->gt($now) => 'held',
                        default => 'available', // an expired hold does not block the seat
                    };
                }

                return [
                    'id' => $seat->id,
                    'label' => $seat->row_label . $seat->seat_number,
                    'number' => $seat->seat_number,
                    'premium' => $seat->type === 'premium',
                    'state' => $state,
                    'price' => $row?->price_cents,
                ];
            }));

        $available = $rows->flatten(1)->where('state', 'available')->count();

        if (auth()->guest()) {
            session(['url.intended' => url()->current()]); // bring the guest back here after sign in
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
            'max' => Showtime::setting('max_seats_per_booking', 6),
            'holdMinutes' => Showtime::setting('hold_minutes', 10),
            'cutoff' => $cutoff,
            'closed' => $now->gte($showtime->starts_at->copy()->subMinutes($cutoff)),
            'now' => $now,
            'surcharge' => Showtime::premiumSurchargeCents(),
        ]);
    }
}