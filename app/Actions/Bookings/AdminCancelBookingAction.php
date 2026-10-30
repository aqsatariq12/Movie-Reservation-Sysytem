<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ShowtimeSeatStatus;
use App\Enums\TicketStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Refund;
use App\Services\StripePaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminCancelBookingAction
{
    public function __construct(
        private StripePaymentService $stripe
    ) {
    }

    public function execute(
        Booking $booking,
        int $adminId,
        string $reason,
        ?string $ipAddress = null
    ): Refund {
        // First cancel the booking and create its refund record.
        $refund = DB::transaction(function () use ($booking, $adminId, $reason, $ipAddress): Refund {
            $booking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== BookingStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'booking' => 'Only confirmed bookings can be cancelled.',
                ]);
            }

            $showtime = $booking->showtime()
                ->lockForUpdate()
                ->firstOrFail();

            $payment = $booking->payments()
                ->where('provider', 'stripe')
                ->where('status', PaymentStatus::Paid->value)
                ->lockForUpdate()
                ->first();

            if (!$payment || blank($payment->provider_payment_id)) {
                throw ValidationException::withMessages([
                    'booking' => 'A successful Stripe payment could not be verified.',
                ]);
            }

            $tickets = $booking->tickets()
                ->lockForUpdate()
                ->get();

            foreach ($tickets as $ticket) {
                if (
                    $ticket->status === TicketStatus::Used
                    || $ticket->used_at !== null
                    || $ticket->scans()->exists()
                ) {
                    throw ValidationException::withMessages([
                        'booking' => 'This booking cannot be cancelled because a ticket has already been used.',
                    ]);
                }
            }

            $showtimeSeats = $showtime->showtimeSeats()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->get();

            if ($showtimeSeats->isEmpty()) {
                throw ValidationException::withMessages([
                    'booking' => 'The seats assigned to this booking could not be verified.',
                ]);
            }

            // Prevent another pending or completed refund for this payment.
            $existingRefund = $payment->refunds()
                ->whereIn('status', [
                    RefundStatus::Pending->value,
                    RefundStatus::Succeeded->value,
                ])
                ->lockForUpdate()
                ->first();

            if ($existingRefund) {
                throw ValidationException::withMessages([
                    'booking' => 'A pending or successful refund already exists for this payment.',
                ]);
            }

            $before = [
                'status' => $booking->status->value,
                'cancelled_at' => $booking->cancelled_at?->toDateTimeString(),
                'payment_id' => $payment->id,
                'payment_status' => $payment->status->value,
                'seat_ids' => $showtimeSeats
                    ->pluck('seat_id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all(),
            ];

            // Void unused tickets.
            foreach ($tickets as $ticket) {
                if ($ticket->status !== TicketStatus::Void) {
                    $ticket->status = TicketStatus::Void;
                    $ticket->save();
                }
            }

            // Release the seats assigned to this booking.
            foreach ($showtimeSeats as $showtimeSeat) {
                $showtimeSeat->status = ShowtimeSeatStatus::Available;
                $showtimeSeat->booking_id = null;
                $showtimeSeat->hold_id = null;
                $showtimeSeat->held_until = null;
                $showtimeSeat->save();
            }

            // Cancel the booking.
            $booking->status = BookingStatus::Cancelled;
            $booking->cancelled_at = now();
            $booking->save();

            // Create the refund record before contacting Stripe.
            $refund = $payment->refunds()->create([
                'amount_cents' => (int) $payment->amount_cents,
                'status' => RefundStatus::Pending,
                'reason' => 'admin_booking_cancelled',
                'requested_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'admin_booking_cancelled',
                'entity_type' => Booking::class,
                'entity_id' => $booking->id,
                'before_data' => $before,
                'after_data' => [
                    'status' => $booking->status->value,
                    'cancelled_at' => $booking->cancelled_at?->toDateTimeString(),
                    'refund_id' => $refund->id,
                    'refund_amount_cents' => (int) $refund->amount_cents,
                    'refund_status' => $refund->status->value,
                    'reason' => trim($reason),
                    'admin_id' => $adminId,
                ],
                'ip_address' => $ipAddress,
            ]);

            return $refund;
        });

        // The database transaction has committed.
        // Now request the refund from Stripe immediately.
        try {
            $payment = $refund->payment;

            $stripeRefund = $this->stripe->createRefund(
                $payment->provider_payment_id,
                (int) $refund->amount_cents,
                'admin-cancel-refund-' . $refund->id
            );

            $stripeStatus = match ((string) $stripeRefund->status) {
                'succeeded' => RefundStatus::Succeeded,
                'failed' => RefundStatus::Failed,
                'canceled' => RefundStatus::Canceled,
                default => RefundStatus::Pending,
            };

            // Save Stripe's actual response.
            // Save Stripe's response without overwriting a webhook update.
            DB::table('refunds')
                ->where('id', $refund->id)
                ->whereNull('provider_refund_id')
                ->update([
                    'provider_refund_id' => $stripeRefund->id,
                    'status' => $stripeStatus->value,
                    'completed_at' => in_array(
                        $stripeStatus,
                        [
                            RefundStatus::Succeeded,
                            RefundStatus::Failed,
                            RefundStatus::Canceled,
                        ],
                        true
                    ) ? now() : null,
                    'updated_at' => now(),
                ]);

            // Reload the refund in case a webhook updated it first.
            $refund->refresh();

            if ($stripeStatus === RefundStatus::Succeeded) {
                $successfulRefundTotal = (int) DB::table('refunds')
                    ->where('payment_id', $payment->id)
                    ->where('status', RefundStatus::Succeeded->value)
                    ->sum('amount_cents');

                if ($successfulRefundTotal >= (int) $payment->amount_cents) {
                    $payment->update([
                        'status' => PaymentStatus::Refunded,
                    ]);
                }
            }
        } catch (Throwable $exception) {
            // Keep the refund pending so reconciliation can retry it.
            Log::error('Immediate admin booking refund failed.', [
                'booking_id' => $booking->id,
                'payment_id' => $refund->payment_id,
                'refund_id' => $refund->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return $refund->fresh();
    }
}
