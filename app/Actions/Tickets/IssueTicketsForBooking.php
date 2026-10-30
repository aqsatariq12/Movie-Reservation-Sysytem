<?php

namespace App\Actions\Tickets;

use App\Models\Booking;
use App\Enums\BookingStatus;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueTicketsForBooking
{
    /**
     * Confirmed booking ke har seat ke liye ek ticket banata hai.
     * Dobara chalane se duplicate nahi banta (booking_seat_id unique hai).
     * Booking confirmed na ho to kuch nahi banata.
     */
    public function handle(Booking $booking): void
    {
       if ($booking->status !== BookingStatus::Confirmed) {
    return;
}

        $seatIds = DB::table('booking_seats')
            ->where('booking_id', $booking->id)
            ->orderBy('id')
            ->pluck('id');

        foreach ($seatIds as $bookingSeatId) {
        Ticket::firstOrCreate(
    ['booking_seat_id' => $bookingSeatId],
    [
        'booking_id' => $booking->id,
        'token'      => Str::random(40),
        'status'     => 'valid',
    ]
);
        }
    }
}