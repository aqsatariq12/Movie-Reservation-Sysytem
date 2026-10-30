<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\StripePaymentService;
use App\Mail\BookingConfirmedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcileStripePayments extends Command
{
    protected $signature = 'stripe:reconcile';

    protected $description =
        'Reconcile pending Stripe Checkout payments';

    public function handle(StripePaymentService $stripe): int
    {
        $payments = Payment::query()
            ->where('provider', 'stripe')
            ->where('status', 'pending')
            ->whereNotNull('provider_session_id')
            ->orderBy('id')
            ->get();

        $checked = 0;
        $updated = 0;
        $errors = 0;

        foreach ($payments as $payment) {
            try {
                // Get the latest state directly from Stripe.
                $session = $stripe->retrieveCheckoutSession(
                    $payment->provider_session_id
                );

                $booking = Booking::find($payment->booking_id);

                if (!$booking) {
                    $this->warn(
                        "Payment {$payment->id}: booking not found."
                    );
                    continue;
                }

                // Verify that this Stripe session belongs to this booking.
                if (
                    (string) ($session->metadata->booking_id ?? '')
                        !== (string) $booking->id ||
                    (string) ($session->metadata->booking_reference ?? '')
                        !== (string) $booking->booking_reference ||
                    (int) ($session->amount_total ?? -1)
                        !== (int) $payment->amount_cents ||
                    strtolower((string) ($session->currency ?? ''))
                        !== strtolower((string) $payment->currency)
                ) {
                    throw new \RuntimeException(
                        'Stripe session metadata, amount or currency mismatch.'
                    );
                }

                $checked++;

                // A paid session can confirm only a payable booking.

                // Handle successful payments, including late payments
                // received after a booking has been cancelled.
                if ($session->payment_status === 'paid') {
                    $paymentIntent = $session->payment_intent ?? null;

                    if (is_object($paymentIntent)) {
                        $paymentIntent = $paymentIntent->id ?? null;
                    }

                    if (!is_string($paymentIntent) || $paymentIntent === '') {
                        $this->error(
                            "Payment {$payment->id}: missing Stripe payment intent."
                        );

                        continue;
                    }

                    $outcome = DB::transaction(function () use (
                        $payment,
                        $paymentIntent
                    ) {
                        $lockedPayment = Payment::whereKey($payment->id)
                            ->lockForUpdate()
                            ->first();

                        $lockedBooking = Booking::whereKey(
                            $payment->booking_id
                        )->lockForUpdate()->first();

                        if (
                            !$lockedPayment ||
                            !$lockedBooking ||
                            $lockedPayment->status !== 'pending'
                        ) {
                            return false;
                        }

                        $lockedPayment->update([
                            'status' => 'paid',
                            'provider_payment_id' => $paymentIntent,
                            'paid_at' => $lockedPayment->paid_at ?? now(),
                        ]);

                        if ($lockedBooking->status === 'cancelled') {
                            return 'late_refund';
                        }

                        if (!in_array(
                            $lockedBooking->status,
                            ['pending_payment', 'confirmed'],
                            true
                        )) {
                            return false;
                        }

                       if (!in_array(
    $lockedBooking->status,
    ['pending_payment', 'confirmed'],
    true
)) {
    return false;
}

if ($lockedBooking->status === 'pending_payment') {
    $lockedBooking->update([
        'status' => 'confirmed',
        'confirmed_at' => now(),
    ]);
}

// Convert held seats into sold seats.
if ($lockedBooking->hold_id) {
    DB::table('showtime_seats')
        ->where('hold_id', $lockedBooking->hold_id)
        ->where('status', 'held')
        ->update([
            'status' => 'sold',
            'booking_id' => $lockedBooking->id,
            'hold_id' => null,
            'held_until' => null,
            'updated_at' => now(),
        ]);

    // Convert the active hold.
    DB::table('holds')
        ->where('id', $lockedBooking->hold_id)
        ->where('status', 'active')
        ->update([
            'status' => 'converted',
            'updated_at' => now(),
        ]);
}

return 'confirmed';
                    });


if ($outcome === 'late_refund') {
    $refundRecord = DB::table('refunds')
        ->where('payment_id', $payment->id)
        ->where('reason', 'late_payment_after_booking_cancelled')
        ->orderByDesc('id')
        ->first();

    if ($refundRecord && $refundRecord->provider_refund_id) {
        $this->info(
            "Payment {$payment->id}: refund already recorded."
        );

        continue;
    }

    if (!$refundRecord) {
        DB::table('refunds')->insert([
            'payment_id' => $payment->id,
            'amount_cents' => (int) $payment->amount_cents,
            'provider_refund_id' => null,
            'status' => 'pending',
            'reason' => 'late_payment_after_booking_cancelled',
            'requested_at' => now(),
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->info(
        "Payment {$payment->id}: late refund saved as pending."
    );

    continue;
}


                   if (in_array($outcome, ['confirmed', 'paid'], true)) {
    $updated++;

    if ($outcome === 'confirmed') {
        try {
            $confirmedBooking = Booking::with([
                'user',
                'showtime.movie',
                'bookingSeats.seat',
            ])->find($payment->booking_id);

            if ($confirmedBooking && $confirmedBooking->user?->email) {
                Mail::to($confirmedBooking->user->email)
                    ->send(new BookingConfirmedMail($confirmedBooking));

                $this->info(
                    "Booking {$confirmedBooking->id}: confirmation email sent."
                );
            }
        } catch (Throwable $exception) {
            Log::error('Booking confirmation email failed.', [
                'booking_id' => $payment->booking_id,
                'message' => $exception->getMessage(),
            ]);

            $this->warn(
                "Booking {$payment->booking_id}: email could not be sent."
            );
        }
    }

    $this->info(
        "Payment {$payment->id}: marked paid; booking confirmed."
    );
}

                    continue;
                }


                // Release seats only when Stripe confirms session expiry.
                if ($session->status === 'expired') {
                    $changed = DB::transaction(function () use ($payment) {
                        $lockedPayment = Payment::whereKey($payment->id)
                            ->lockForUpdate()
                            ->first();

                        $lockedBooking = Booking::whereKey(
                            $payment->booking_id
                        )->lockForUpdate()->first();

                        if (
                            !$lockedPayment ||
                            !$lockedBooking ||
                            $lockedPayment->status !== 'pending'
                        ) {
                            return false;
                        }

                        $lockedPayment->update([
                            'status' => 'expired',
                        ]);

                        if (
                            $lockedBooking->status !== 'pending_payment'
                        ) {
                            return true;
                        }

                        $lockedBooking->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                        ]);

                        if ($lockedBooking->hold_id) {
                            DB::table('holds')
                                ->where('id', $lockedBooking->hold_id)
                                ->where('status', 'active')
                                ->update([
                                    'status' => 'expired',
                                    'updated_at' => now(),
                                ]);

                            DB::table('showtime_seats')
                                ->where('hold_id', $lockedBooking->hold_id)
                                ->where('status', 'held')
                                ->update([
                                    'status' => 'available',
                                    'hold_id' => null,
                                    'booking_id' => null,
                                    'held_until' => null,
                                    'updated_at' => now(),
                                ]);
                        }

                        return true;
                    });

                    if ($changed) {
                        $updated++;
                        $this->info(
                            "Payment {$payment->id}: expired; pending booking cancelled."
                        );
                    }

                    continue;
                }

                // Open sessions and unpaid sessions are left unchanged.
                $this->line(
                    "Payment {$payment->id}: no update needed."
                );
            } catch (Throwable $exception) {
                $errors++;

                Log::error('Stripe reconciliation failed.', [
                    'payment_id' => $payment->id,
                    'session_id' => $payment->provider_session_id,
                    'message' => $exception->getMessage(),
                ]);

                $this->error(
                    "Payment {$payment->id}: {$exception->getMessage()}"
                );
            }
        }

        // Retry late refunds that do not yet have a Stripe refund ID.
        $pendingRefunds = DB::table('refunds')
            ->join('payments', 'payments.id', '=', 'refunds.payment_id')
            ->where('payments.provider', 'stripe')
            ->where('payments.status', 'paid')
            ->whereNotNull('payments.provider_payment_id')
            ->where('refunds.reason', 'late_payment_after_booking_cancelled')
            ->where('refunds.status', 'pending')
            ->whereNull('refunds.provider_refund_id')
            ->select(
                'refunds.id as refund_id',
                'refunds.payment_id',
                'refunds.amount_cents',
                'payments.provider_payment_id'
            )
            ->orderBy('refunds.id')
            ->get();

        foreach ($pendingRefunds as $pendingRefund) {
            try {
                $refund = $stripe->createRefund(
                    $pendingRefund->provider_payment_id,
                    (int) $pendingRefund->amount_cents,
                    'late-refund-payment-' . $pendingRefund->payment_id
                );

                $refundStatus = match ((string) $refund->status) {
                    'succeeded' => 'succeeded',
                    'failed' => 'failed',
                    'canceled' => 'canceled',
                    default => 'pending',
                };

                DB::table('refunds')
                    ->where('id', $pendingRefund->refund_id)
                    ->update([
                        'provider_refund_id' => $refund->id,
                        'status' => $refundStatus,
                        'completed_at' => in_array(
                            $refundStatus,
                            ['succeeded', 'failed', 'canceled'],
                            true
                        ) ? now() : null,
                        'updated_at' => now(),
                    ]);

                if ($refundStatus === 'succeeded') {
                    Payment::whereKey($pendingRefund->payment_id)
                        ->update(['status' => 'refunded']);
                }

                $updated++;

                $this->info(
                    "Payment {$pendingRefund->payment_id}: pending late refund is {$refundStatus}."
                );
            } catch (Throwable $exception) {
                $errors++;

                Log::error('Late refund retry failed.', [
                    'payment_id' => $pendingRefund->payment_id,
                    'refund_id' => $pendingRefund->refund_id,
                    'message' => $exception->getMessage(),
                ]);

                $this->error(
                    "Payment {$pendingRefund->payment_id}: late refund retry failed."
                );
            }
        }


        $this->newLine();
        $this->info("Checked: {$checked}");
        $this->info("Updated: {$updated}");
        $this->info("Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
