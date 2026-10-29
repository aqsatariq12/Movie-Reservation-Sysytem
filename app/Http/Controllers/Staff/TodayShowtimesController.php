<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class TodayShowtimesController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();

        $showtimes = Showtime::query()
            ->with(['movie', 'screen'])
            ->whereDate('starts_at', $today)
            ->orderBy('starts_at')
            ->get()
            ->map(function (Showtime $showtime) {
                $bookingIds = DB::table('bookings')
                    ->where('showtime_id', $showtime->id)
                    ->where('status', 'confirmed')
                    ->select('id');

                $showtime->tickets_expected = DB::table('tickets')
                    ->whereIn('booking_id', $bookingIds)
                    ->count();

                $showtime->guests_admitted = DB::table('ticket_scans')
                    ->where('showtime_id', $showtime->id)
                    ->where('result', 'admitted')
                    ->count();

                $showtime->tickets_scanned = $showtime->guests_admitted;

                return $showtime;
            });

        $stats = [
            [
                'Scheduled shows',
                (string) $showtimes->count(),
            ],
            [
                'Tickets expected',
                (string) $showtimes->sum('tickets_expected'),
            ],
            [
                'Guests admitted',
                (string) $showtimes->sum('guests_admitted'),
            ],
        ];

        return view('staff.todays-showtimes', [
            'showtimes' => $showtimes,
            'stats' => $stats,
            'today' => now(),
        ]);
    }
}
