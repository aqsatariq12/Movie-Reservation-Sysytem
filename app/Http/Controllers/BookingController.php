<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $filter = in_array($request->query('filter'), ['upcoming', 'past'], true)
            ? $request->query('filter')
            : 'all';

        // "Upcoming" = a live booking (confirmed or awaiting payment) for a future showtime.
        $upcomingScope = fn ($query) => $query
            ->whereIn('status', ['confirmed', 'pending_payment'])
            ->whereHas('showtime', fn ($s) => $s->where('starts_at', '>=', now()));

        // Always scoped to the logged-in customer: nobody sees another user's bookings.
        $bookings = Booking::query()
            ->where('user_id', $userId)
            ->with([
                'showtime.movie',
                'showtime.screen',
                'bookingSeats.seat',
                'hold',
                'payments.refunds',
            ])
            ->when($filter === 'upcoming', $upcomingScope)
            ->when($filter === 'past', fn ($q) => $q->whereNot(fn ($w) => $upcomingScope($w)))
            ->latest('created_at')
            ->get();

        $upcomingCount = Booking::where('user_id', $userId)->where($upcomingScope)->count();
        $allCount = Booking::where('user_id', $userId)->count();

        $counts = [
            'upcoming' => $upcomingCount,
            'past'     => $allCount - $upcomingCount,
            'all'      => $allCount,
        ];

        // Next confirmed booking shown as the big card.
        $featured = $bookings
            ->where('status', 'confirmed')
            ->filter(fn ($b) => $b->showtime->starts_at->isFuture())
            ->sortBy(fn ($b) => $b->showtime->starts_at)
            ->first();

        // Pending payments whose hold is still active.
        $pending = $bookings->filter(fn ($b) => $b->status === 'pending_payment'
            && $b->hold
            && $b->hold->status === 'active'
            && $b->hold->expires_at->isFuture());

        return view('bookings.index', compact('bookings', 'counts', 'filter', 'featured', 'pending'));
    }
}