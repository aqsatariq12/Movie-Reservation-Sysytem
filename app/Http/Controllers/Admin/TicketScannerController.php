<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\ScanResult;
use App\Enums\TicketStatus;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Mail\TicketScannedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;

class TicketScannerController extends Controller
{
    public function index(Request $request): View
    {
        $showtimes = Showtime::query()
            ->with(['movie', 'screen'])
            ->where('starts_at', '>=', now()->subHours(3))
            ->orderBy('starts_at')
            ->get();

        $selectedShowtimeId = $request->query(
            'showtime_id',
            old('showtime_id')
        );

        $selectedShowtime = $showtimes->firstWhere(
            'id',
            (int) $selectedShowtimeId
        );

        $recentScans = TicketScan::query()
            ->with([
                'ticket.bookingSeat.seat',
            ])
            ->latest('scanned_at')
            ->limit(10)
            ->get();

        $admittedCount = 0;
        $ticketsSold = 0;

        if ($selectedShowtime) {
            $ticketsSold = Ticket::query()
                ->whereHas('booking', function ($query) use ($selectedShowtime) {
                    $query->where('showtime_id', $selectedShowtime->id)
                        ->where('status', BookingStatus::Confirmed);
                })
                ->count();

            $admittedCount = Ticket::query()
                ->whereHas('booking', function ($query) use ($selectedShowtime) {
                    $query->where('showtime_id', $selectedShowtime->id)
                        ->where('status', BookingStatus::Confirmed);
                })
                ->where('status', TicketStatus::Used)
                ->count();
        }

        $view = $request->user()->isstaff()
    ? 'staff.ticket-scanner'
    : 'admin.ticket-scanner';

return view($view, compact(
            'showtimes',
            'selectedShowtimeId',
            'selectedShowtime',
            'recentScans',
            'admittedCount',
            'ticketsSold'
        ));
    }

    public function scan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'showtime_id' => ['required', 'integer', 'exists:showtimes,id'],
        ]);

        $token = trim($validated['token']);
        $selectedShowtimeId = (int) $validated['showtime_id'];

        $ticket = Ticket::query()
            ->where('token', $token)
            ->first();

        if (! $ticket) {
            $this->recordScan(
                null,
                $selectedShowtimeId,
                hash('sha256', $token),
                ScanResult::Invalid,
                $request->user()->id
            );
return redirect()
    ->route(
        $request->user()->isStaff()
            ? 'ui.staff.admitted'
            : 'admin.ticket-scanner',
        ['showtime_id' => $selectedShowtimeId]
    )
    ->with('scan_result', [
        'type' => 'error',
        'message' => 'Invalid ticket QR code.',
    ]);

        }

        $result = DB::transaction(function () use (
            $ticket,
            $request,
            $token,
            $selectedShowtimeId
        ) {
            $lockedTicket = Ticket::query()
                ->whereKey($ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            $booking = $lockedTicket->booking()
                ->with(['showtime.movie', 'showtime.screen'])
                ->first();

            $showtime = $booking?->showtime;
            $now = now();

            $scanResult = match (true) {
                ! $booking || ! $showtime => ScanResult::Invalid,
                (int) $showtime->id !== $selectedShowtimeId => ScanResult::WrongShowtime,
                $lockedTicket->status === TicketStatus::Void => ScanResult::Void,
                $lockedTicket->status === TicketStatus::Used => ScanResult::AlreadyUsed,
                $booking->status !== BookingStatus::Confirmed => ScanResult::Invalid,
                $now->lt($showtime->starts_at->copy()->subHour()) => ScanResult::TooEarly,
                $now->gt($showtime->starts_at->copy()->addMinutes(30)) => ScanResult::Expired,
                default => ScanResult::Admitted,
            };
        if ($scanResult === ScanResult::Admitted) {
                $lockedTicket->status = TicketStatus::Used;
                $lockedTicket->used_at = $now;
                $lockedTicket->save();
            }

            TicketScan::create([
                'ticket_id' => $lockedTicket->id,
                'user_id' => $request->user()->id,
                'showtime_id' => $selectedShowtimeId,
                'token_hash' => hash('sha256', $token),
                'result' => $scanResult,
                'scanned_at' => $now,
            ]);

            $seat = $lockedTicket->bookingSeat()
                ->with('seat')
                ->first()?->seat;

            return [
                'result' => $scanResult,
                'movie' => $showtime?->movie?->title,
                'seat' => $seat
                    ? ($seat->row_label ?? '') . ($seat->seat_number ?? '')
                    : null,
            ];
        });

        $messages = [
            ScanResult::Admitted->value => ['success', 'Ticket accepted. Entry allowed.'],
            ScanResult::AlreadyUsed->value => ['error', 'This ticket has already been scanned.'],
            ScanResult::WrongShowtime->value => ['error', 'This ticket is for a different showtime.'],
            ScanResult::TooEarly->value => ['error', 'Entry is not open yet.'],
            ScanResult::Expired->value => ['error', 'The entry window has closed.'],
            ScanResult::Void->value => ['error', 'This ticket is void.'],
            ScanResult::Invalid->value => ['error', 'Ticket or booking is invalid.'],
        ];

        [$type, $message] = $messages[$result['result']->value];
        if ($result['result'] === ScanResult::Admitted) {
    try {
        $ticketScan = TicketScan::with([
            'ticket.bookingSeat.booking.user',
            'ticket.bookingSeat.booking.showtime.movie',
            'ticket.bookingSeat.seat',
        ])
            ->where('ticket_id', $ticket->id)
            ->where('result', ScanResult::Admitted)
            ->latest('scanned_at')
            ->first();

        $customerEmail = $ticketScan?->ticket?->bookingSeat?->booking?->user?->email;

        if ($ticketScan && $customerEmail) {
            Mail::to($customerEmail)->send(
                new TicketScannedMail($ticketScan)
            );
        }
    } catch (\Throwable $exception) {
        \Illuminate\Support\Facades\Log::error(
            'Ticket scan email failed.',
            [
                'ticket_id' => $ticket->id,
                'message' => $exception->getMessage(),
            ]
        );
    }
}
$route = $request->user()->isStaff()
    ? 'ui.staff.admitted'
    : 'admin.ticket-scanner';

return redirect()
    ->route($route, [
        'showtime_id' => $selectedShowtimeId,
    ])
    ->with('scan_result', [
        'type' => $type,
        'message' => $message,
        'movie' => $result['movie'],
        'seat' => $result['seat'],
    ]);

    }
private function recordScan(
        ?int $ticketId,
        ?int $showtimeId,
        string $tokenHash,
        ScanResult $result,
        int $userId
    ): void {
        TicketScan::create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'showtime_id' => $showtimeId,
            'token_hash' => $tokenHash,
            'result' => $result,
            'scanned_at' => now(),
        ]);
    }
}
