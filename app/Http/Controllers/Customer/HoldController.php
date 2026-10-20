<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Holds\HoldSeatsAction;
use App\Actions\Holds\ReleaseHoldAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\HoldSeatsRequest;
use App\Models\Hold;
use App\Models\Showtime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class HoldController extends Controller
{
    public function store(HoldSeatsRequest $request, Showtime $showtime, HoldSeatsAction $hold)
    {
        $booking = $hold->execute($request->user(), $showtime, $request->seatIds());

        return redirect()->route('holds.show', $booking->hold_id);
    }

    public function show(Hold $hold)
    {
        Gate::authorize('view', $hold);

        // An expired or finished hold has nothing to show: send the customer back to the seat map.
        if ($hold->status !== 'active' || $hold->expires_at->lte(now())) {
            return redirect()->route('showtimes.seats', $hold->showtime_id)
                ->with('status', 'This hold is no longer active. Please choose your seats again.');
        }

        $booking = $hold->booking()->with('bookingSeats.seat')->firstOrFail();
        $showtime = $hold->showtime()->with('screen')->firstOrFail();

        // TODO: use the Movie model once the owner's branch is merged
        $movie = DB::table('movies')->where('id', $showtime->movie_id)->first();

        $seats = $booking->bookingSeats
            ->map(fn ($bookingSeat) => [
                'row' => $bookingSeat->seat->row_label,
                'number' => $bookingSeat->seat->seat_number,
                'label' => $bookingSeat->seat->row_label . $bookingSeat->seat->seat_number,
                'type' => ucfirst($bookingSeat->seat->type),
                'price' => $bookingSeat->price_cents,
            ])
            ->sortBy([['row', 'asc'], ['number', 'asc']])
            ->values();

        return view('customer.hold', [
            'hold' => $hold,
            'booking' => $booking,
            'showtime' => $showtime,
            'screen' => $showtime->screen,
            'movie' => $movie,
            'seats' => $seats,
            'now' => now(),
            'surcharge' => Showtime::premiumSurchargeCents(),
            'cancelUntil' => $showtime->starts_at->copy()->subHours(Showtime::setting('cancellation_window_hours', 2)),
        ]);
    }

    public function destroy(Hold $hold, ReleaseHoldAction $release)
    {
        Gate::authorize('delete', $hold);

        $release->execute($hold, 'released');

        return redirect()->route('showtimes.seats', $hold->showtime_id)
            ->with('status', 'Hold released. Choose your seats again.');
    }
}