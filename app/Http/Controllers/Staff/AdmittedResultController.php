<?php

namespace App\Http\Controllers\Staff;

use App\Enums\BookingStatus;
use App\Enums\ScanResult;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketScan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmittedResultController extends Controller
{
    public function index(Request $request): View
    {
        $latestScan = TicketScan::query()
            ->with([
                'ticket.bookingSeat.seat',
                'ticket.booking.showtime.movie',
                'ticket.booking.showtime.screen',
            ])
            ->where('user_id', $request->user()->id)
            ->latest('scanned_at')
            ->first();

     $showtime = $latestScan?->ticket?->booking?->showtime;

if (! $showtime && $latestScan?->showtime_id) {
    $showtime = Showtime::with(['movie', 'screen'])
        ->find($latestScan->showtime_id);
}


        $recentScans = TicketScan::query()
            ->with(['ticket.bookingSeat.seat'])
            ->where('user_id', $request->user()->id)
            ->latest('scanned_at')
            ->limit(10)
            ->get();

        $ticketsSold = 0;
        $admittedCount = 0;

        if ($showtime) {
            $ticketsSold = Ticket::query()
                ->whereHas('booking', function ($query) use ($showtime) {
                    $query->where('showtime_id', $showtime->id)
                        ->where('status', BookingStatus::Confirmed);
                })
                ->count();

           $admittedCount = Ticket::query()
    ->whereHas('booking', function ($query) use ($showtime) {
        $query->where('showtime_id', $showtime->id)
            ->where('status', BookingStatus::Confirmed);
    })
    ->where('status', TicketStatus::Used)
    ->count();
        }

        $seat = $latestScan?->ticket?->bookingSeat?->seat;

        $seatLabel = $seat
            ? ($seat->row_label ?? '') . ($seat->seat_number ?? '')
            : null;

        $ticketLabel = $latestScan?->ticket_id
            ? 'T-' . str_pad(
                (string) $latestScan->ticket_id,
                4,
                '0',
                STR_PAD_LEFT
            )
            : null;

        $scanResult = $latestScan?->result;
        $scanResultValue = $scanResult instanceof ScanResult
            ? $scanResult->value
            : $scanResult;

        return view('staff.admitted-result', compact(
            'latestScan',
            'recentScans',
            'showtime',
            'seatLabel',
            'ticketLabel',
            'scanResultValue',
            'ticketsSold',
            'admittedCount'
        ));
    }
}
