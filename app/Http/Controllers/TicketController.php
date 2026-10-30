<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\IssueTicketsForBooking;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function show(Request $request, Booking $booking, IssueTicketsForBooking $issue)
    {
        // Sirf apni booking.
        abort_unless((int) $booking->user_id === (int) $request->user()->id, 403);

        // Sirf confirmed booking ke tickets.
     if ($booking->status !== \App\Enums\BookingStatus::Confirmed) {
            return redirect()->route('booking.recovery-states', $booking);
        }

        $issue->handle($booking);

        $rows = DB::table('tickets')
            ->join('booking_seats', 'booking_seats.id', '=', 'tickets.booking_seat_id')
            ->join('seats', 'seats.id', '=', 'booking_seats.seat_id')
            ->where('tickets.booking_id', $booking->id)
            ->orderBy('seats.row_label')->orderBy('seats.seat_number')
            ->get([
                'tickets.id', 'tickets.token', 'tickets.status',
                'seats.row_label', 'seats.seat_number', 'seats.type',
            ]);

        $tickets = $rows->map(fn ($t) => [
            'seat'   => $t->row_label.$t->seat_number,
            'type'   => ucfirst($t->type),
            'code'   => 'T-'.str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT).'-'.str_pad((string) $t->id, 2, '0', STR_PAD_LEFT),
            'status' => $t->status,
            'qr'     => $this->qrSvg($t->token), // QR me sirf opaque token
        ])->all();

        return view('bookings.tickets', [
            'booking' => $booking->load('showtime.movie', 'showtime.screen'),
            'tickets' => $tickets,
        ]);
    }
private function qrSvg(string $token): ?string
{
    if (! class_exists(\BaconQrCode\Writer::class)) {
        return null;
    }

    $renderer = new \BaconQrCode\Renderer\ImageRenderer(
        new \BaconQrCode\Renderer\RendererStyle\RendererStyle(300, 10),
        new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
    );

    $svg = (new \BaconQrCode\Writer($renderer))
        ->writeString($token);

    // Remove XML declaration only; preserve the QR SVG itself.
    return preg_replace('/<\?xml.*?\?>\s*/s', '', $svg);
}
}