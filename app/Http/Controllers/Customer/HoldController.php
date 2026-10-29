<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Holds\HoldSeatsAction;
use App\Actions\Holds\ReleaseHoldAction;
use App\Enums\HoldStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\HoldSeatsRequest;
use App\Models\Hold;
use App\Models\Setting;
use App\Models\Showtime;
use Illuminate\Support\Facades\Gate;
use App\Mail\BookingCreatedMail;
use Illuminate\Support\Facades\Mail;

class HoldController extends Controller
{
  public function store(
    HoldSeatsRequest $request,
    Showtime $showtime,
    HoldSeatsAction $hold
) {
    $booking = $hold->execute(
        $request->user(),
        $showtime,
        $request->seatIds()
    );

    $booking->load([
        'user',
        'showtime.movie',
        'bookingSeats.seat',
    ]);

    Mail::to($booking->user->email)
        ->send(new BookingCreatedMail($booking));

    return redirect()->route('holds.show', $booking->hold_id);
}

    public function show(Hold $hold)
    {
        Gate::authorize('view', $hold);

        if (
            $hold->status !== HoldStatus::Active
            || $hold->expires_at->lte(now())
        ) {
            return redirect()
                ->route('showtimes.seats', $hold->showtime_id)
                ->with(
                    'status',
                    'This hold is no longer active. Please choose your seats again.'
                );
        }

        $booking = $hold->booking()
            ->with('bookingSeats.seat')
            ->firstOrFail();

        $showtime = $hold->showtime()
            ->with(['screen', 'movie.genres'])
            ->firstOrFail();

        $movie = $showtime->movie;

        $seats = $booking->bookingSeats
            ->map(fn ($bookingSeat) => [
                'row' => $bookingSeat->seat->row_label,
                'number' => $bookingSeat->seat->seat_number,
                'label' => $bookingSeat->seat->row_label
                    . $bookingSeat->seat->seat_number,
                'type' => ucfirst($bookingSeat->seat->type->value),
                'price' => $bookingSeat->price_cents,
            ])
            ->sortBy([
                ['row', 'asc'],
                ['number', 'asc'],
            ])
            ->values();

        return view('customer.hold', [
            'hold' => $hold,
            'booking' => $booking,
            'showtime' => $showtime,
            'screen' => $showtime->screen,
            'movie' => $movie,
            'seats' => $seats,
            'now' => now(),
            'surcharge' => Setting::readInt(
                'premium_surcharge_cents',
                200
            ),
            'cancelUntil' => $showtime->starts_at
                ->copy()
                ->subHours(
                    Setting::readInt('cancellation_window_hours', 2)
                ),
        ]);
    }

    public function destroy(Hold $hold, ReleaseHoldAction $release)
    {
        Gate::authorize('delete', $hold);

        $release->execute($hold, 'released');

        return redirect()
            ->route('showtimes.seats', $hold->showtime_id)
            ->with('status', 'Hold released. Choose your seats again.');
    }
}
