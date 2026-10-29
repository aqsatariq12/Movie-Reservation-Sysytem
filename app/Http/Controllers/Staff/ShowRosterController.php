<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShowRosterController extends Controller
{
    public function index(Request $request): View
    {
        $showtimes = DB::table('showtimes')
            ->join('movies', 'movies.id', '=', 'showtimes.movie_id')
            ->join('screens', 'screens.id', '=', 'showtimes.screen_id')
            ->select(
                'showtimes.id',
                'showtimes.starts_at',
                'movies.title as movie_title',
                'screens.name as screen_name'
            )
            ->orderByDesc('showtimes.starts_at')
            ->get();

        $selectedShowtimeId = $request->query('showtime_id');

        if (!$selectedShowtimeId || !$showtimes->contains('id', (int) $selectedShowtimeId)) {
            $selectedShowtimeId = $showtimes->first()?->id;
        }

        $bookings = collect();

        if ($selectedShowtimeId) {
            $bookings = DB::table('bookings')
                ->join('users', 'users.id', '=', 'bookings.user_id')
                ->where('bookings.showtime_id', $selectedShowtimeId)
                ->where('bookings.status', 'confirmed')
                ->select(
                    'bookings.id',
                    'bookings.booking_reference',
                    'users.name as customer_name'
                )
                ->orderBy('bookings.created_at')
                ->get()
                ->map(function ($booking) use ($selectedShowtimeId){
                    $seats = DB::table('booking_seats')
                        ->join('seats', 'seats.id', '=', 'booking_seats.seat_id')
                        ->where('booking_seats.booking_id', $booking->id)
                        ->orderBy('seats.row_label')
                        ->orderBy('seats.seat_number')
                        ->get(['seats.row_label', 'seats.seat_number']);

                    $booking->seat_labels = $seats
                        ->map(fn ($seat) => $seat->row_label . $seat->seat_number)
                        ->implode(', ');

                    $booking->seat_count = $seats->count();

                    $booking->admitted_count = DB::table('tickets')
                        ->join('ticket_scans', 'ticket_scans.ticket_id', '=', 'tickets.id')
                        ->where('tickets.booking_id', $booking->id)
                        ->where('ticket_scans.showtime_id', $selectedShowtimeId)
                        ->where('ticket_scans.result', 'admitted')
                        ->distinct('tickets.id')
                        ->count('tickets.id');

                    return $booking;
                });
        }

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $bookings = $bookings->filter(
                fn ($booking) => str_contains(
                    strtolower($booking->booking_reference),
                    strtolower($search)
                )
            )->values();
        }

        $admittedTotal = $bookings->sum('admitted_count');
        $seatTotal = $bookings->sum('seat_count');

        return view('staff.show-roster', [
            'showtimes' => $showtimes,
            'selectedShowtimeId' => $selectedShowtimeId,
            'bookings' => $bookings,
            'search' => $search,
            'admittedTotal' => $admittedTotal,
            'seatTotal' => $seatTotal,
            'confirmedTotal' => $bookings->count(),
        ]);
    }
}
