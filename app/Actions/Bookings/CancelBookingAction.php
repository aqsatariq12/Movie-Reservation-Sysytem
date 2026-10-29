<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ShowtimeSeatStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelBookingAction
{
    public function execute(Booking $booking, int $userId): Refund
    {
        return DB::transaction(function () use ($booking, $userId) {
            // Lock the booking so two cancellation requests cannot run
            // against it at the same time.
            $booking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $booking->user_id !== $userId) {
                abort(403);
            }

            if ($booking->status !== BookingStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'booking' => 'Only confirmed bookings can be cancelled.',
                ]);
            }

            // Lock the showtime and re-check the cancellation deadline.
            $showtime = $booking->showtime()
                ->lockForUpdate()
                ->firstOrFail();

            $deadline = $showtime->starts_at->copy()->subHours(
                Setting::readInt('cancellation_window_hours', 2)
            );

            if (now()->greaterThanOrEqualTo($deadline)) {
                throw ValidationException::withMessages([
                    'booking' => 'This booking can no longer be cancelled for a refund.',
                ]);
            }

            // Lock the payment records and require a successful Stripe payment.
            $payment = $booking->payments()
                ->where('provider', 'stripe')
                ->where('status', PaymentStatus::Paid->value)
                ->lockForUpdate()
                ->first();

            if (! $payment || blank($payment->provider_payment_id)) {
                throw ValidationException::withMessages([
                    'booking' => 'A confirmed Stripe payment could not be verified.',
                ]);
            }

            // Lock tickets and refuse cancellation if any ticket has been scanned.
            $tickets = $booking->tickets()
                ->lockForUpdate()
                ->get();

            if ($tickets->contains(
                fn ($ticket) => $ticket->status === TicketStatus::Used
                    || $ticket->used_at !== null
                    || $ticket->scans()->exists()
            )) {
                throw ValidationException::withMessages([
                    'booking' => 'This booking cannot be cancelled because a ticket has already been used.',
                ]);
            }

            // Lock only the seats assigned to this booking.
            $showtimeSeats = $showtime->showtimeSeats()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->get();

            if ($showtimeSeats->isEmpty()) {
                throw ValidationException::withMessages([
                    'booking' => 'The seats for this booking could not be verified.',
                ]);
            }

            // Prevent duplicate customer-cancellation refund requests.
            $existingRefund = $payment->refunds()
                ->where('reason', 'customer_booking_cancelled')
                ->whereIn('status', [
                    RefundStatus::Pending->value,
                    RefundStatus::Succeeded->value,
                ])
                ->lockForUpdate()
                ->first();

            if ($existingRefund) {
                throw ValidationException::withMessages([
                    'booking' => 'A cancellation refund has already been requested for this booking.',
                ]);
            }

            // Void unused tickets.
            foreach ($tickets as $ticket) {
                if ($ticket->status !== TicketStatus::Void) {
                    $ticket->status = TicketStatus::Void;
                    $ticket->save();
                }
            }

            // Release only the seats assigned to this booking.
            foreach ($showtimeSeats as $showtimeSeat) {
                $showtimeSeat->status = ShowtimeSeatStatus::Available;
                $showtimeSeat->booking_id = null;
                $showtimeSeat->hold_id = null;
                $showtimeSeat->held_until = null;
                $showtimeSeat->save();
            }

            // Mark the booking as cancelled immediately. Stripe refund
            // processing will be handled separately and safely.
            $booking->status = BookingStatus::Cancelled;
            $booking->cancelled_at = now();
            $booking->save();

            // Record the full refund request; do not contact Stripe here.
            return $payment->refunds()->create([
                'amount_cents' => (int) $payment->amount_cents,
                'status' => RefundStatus::Pending,
                'reason' => 'customer_booking_cancelled',
                'requested_at' => now(),
            ]);
        });
    }
}
