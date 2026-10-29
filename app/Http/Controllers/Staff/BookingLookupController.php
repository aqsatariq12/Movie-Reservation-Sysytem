<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingLookupController extends Controller
{
    public function index(Request $request): View
    {
        $reference = trim((string) $request->query('reference', ''));

        $booking = null;
        $tickets = collect();

        if ($reference !== '') {
            $booking = Booking::query()
                ->with([
                    'user',
                    'showtime.movie',
                    'showtime.screen',
                    'bookingSeats.seat',
                    'tickets.bookingSeat.seat',
                    'tickets.scans' => function ($query) {
                        $query->latest('scanned_at');
                    },
                ])
                ->where('booking_reference', $reference)
                ->first();

            if ($booking) {
                $tickets = $booking->tickets;
            }
        }

        return view('staff.booking-lookup', compact(
            'reference',
            'booking',
            'tickets'
        ));
    }
}
