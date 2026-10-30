<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;
use App\Services\StripePaymentService;
use App\Actions\Tickets\IssueTicketsForBooking;

class StripeWebhookController extends Controller
{
    public function __construct(
        private IssueTicketsForBooking $issueTickets
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('stripe.webhook_secret');

        if (!is_string($secret) || $secret === '') {
            Log::error('Stripe webhook secret is not configured.');

            return response()->json([
                'error' => 'Webhook is not configured.',
            ], 500);
        }

        if (!is_string($signature) || $signature === '') {
            return response()->json([
                'error' => 'Missing Stripe signature.',
            ], 400);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $secret
            );
        } catch (UnexpectedValueException | SignatureVerificationException $exception) {
            Log::warning('Stripe webhook rejected.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'error' => 'Invalid webhook payload or signature.',
            ], 400);
        }

        try {
            $result = DB::transaction(function () use ($event) {
                $object = $event->data->object;

                $sessionEvents = [
                    'checkout.session.completed',
                    'checkout.session.async_payment_succeeded',
                    'checkout.session.async_payment_failed',
                    'checkout.session.expired',
                ];
                $paymentIntentEvents = [
                    'payment_intent.payment_failed',
                ];

                $bookingId = null;

                if (in_array($event->type, $sessionEvents, true)) {
                    $metadataId = $object->metadata->booking_id ?? null;

                    if (is_string($metadataId) && ctype_digit($metadataId)) {
                        $bookingId = (int) $metadataId;
                    }
                }

                // Insert a unique event record. A duplicate event must not
                // be processed a second time.
                try {
                    DB::table('webhook_events')->insert([
                        'provider_event_id' => $event->id,
                        'event_type' => $event->type,
                        'status' => 'processing',
                        'booking_id' => $bookingId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (QueryException $exception) {
                    $existingEvent = DB::table('webhook_events')
                        ->where('provider_event_id', $event->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$existingEvent) {
                        throw $exception;
                    }

                    if (in_array($existingEvent->status, ['processed', 'ignored'], true)) {
                        return 'duplicate';
                    }

                    // Retry an event that was previously left in processing.
                    DB::table('webhook_events')
                        ->where('provider_event_id', $event->id)
                        ->update([
                            'status' => 'processing',
                            'processed_at' => null,
                            'updated_at' => now(),
                        ]);
                }

                $status = 'processed';

                $hasValidSessionMetadata =
                    is_string($object->metadata->booking_id ?? null) &&
                    ctype_digit($object->metadata->booking_id) &&
                    is_string($object->metadata->booking_reference ?? null) &&
                    trim($object->metadata->booking_reference) !== '';

                if (
                    in_array($event->type, $sessionEvents, true) &&
                    !$hasValidSessionMetadata
                ) {
                    // Stripe CLI fixture events are not linked to a real
                    // local booking, so ignore them safely.
                    $status = 'ignored';
                } else {
                    switch ($event->type) {
                        case 'checkout.session.completed':
                        case 'checkout.session.async_payment_succeeded':
                            $this->processCheckoutSession($object, 'paid');
                            break;

                        case 'checkout.session.async_payment_failed':
                            $this->processCheckoutSession($object, 'failed');
                            break;
                        case 'payment_intent.payment_failed':
                            $this->processFailedPaymentIntent($object);
                            break;

                        case 'checkout.session.expired':
                            $this->processCheckoutSession($object, 'expired');
                            break;

                        case 'refund.created':
                        case 'refund.updated':
                            $this->processRefund($object);
                            break;

                        case 'charge.refunded':
                            foreach (($object->refunds->data ?? []) as $refund) {
                                $this->processRefund($refund);
                            }
                            break;

                        default:
                            $status = 'ignored';
                            break;
                    }
                }

                DB::table('webhook_events')
                    ->where('provider_event_id', $event->id)
                    ->update([
                        'status' => $status,
                        'processed_at' => now(),
                        'updated_at' => now(),
                    ]);

                return $status;
            });

            return response()->json([
                'received' => true,
                'status' => $result,
            ]);
        } catch (QueryException $exception) {
            Log::error('Stripe webhook database failure.', [
                'event_id' => $event->id,
                'message' => $exception->getMessage(),
            ]);

            report($exception);

            return response()->json([
                'error' => 'Webhook database operation failed.',
            ], 500);
        } catch (\Throwable $exception) {
            Log::error('Stripe webhook processing failure.', [
                'event_id' => $event->id,
                'event_type' => $event->type,
                'message' => $exception->getMessage(),
            ]);

            report($exception);

            return response()->json([
                'error' => 'Webhook processing failed.',
            ], 500);
        }
    }

    private function processCheckoutSession(
        object $session,
        string $targetStatus
    ): void {
        $sessionId = $session->id ?? null;
        $bookingId = $session->metadata->booking_id ?? null;
        $bookingReference = $session->metadata->booking_reference ?? null;

        if (
            !is_string($sessionId) ||
            !is_string($bookingId) ||
            !ctype_digit($bookingId) ||
            !is_string($bookingReference) ||
            trim($bookingReference) === ''
        ) {
            throw new \RuntimeException(
                'Invalid Stripe checkout session metadata.'
            );
        }

        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where('provider_session_id', $sessionId)
            ->lockForUpdate()
            ->first();

        if (!$payment) {
            throw new \RuntimeException(
                'No local payment matches this Stripe checkout session.'
            );
        }

        $booking = Booking::query()
            ->whereKey($payment->booking_id)
            ->lockForUpdate()
            ->first();

        if (!$booking) {
            throw new \RuntimeException(
                'No local booking matches this Stripe payment.'
            );
        }

        if (
            (string) $booking->id !== $bookingId ||
            (string) $booking->booking_reference !== $bookingReference
        ) {
            throw new \RuntimeException(
                'Stripe booking metadata does not match.'
            );
        }

        if (
            isset($session->amount_total) &&
            (int) $session->amount_total !== (int) $payment->amount_cents
        ) {
            throw new \RuntimeException(
                'Stripe payment amount does not match.'
            );
        }

        if (
            isset($session->currency) &&
            strtolower((string) $session->currency) !==
            strtolower((string) $payment->currency)
        ) {
            throw new \RuntimeException(
                'Stripe currency does not match.'
            );
        }

        if ($payment->status === \App\Enums\PaymentStatus::Refunded) {
            return;
        }

        if (
            $payment->status === \App\Enums\PaymentStatus::Paid &&
            $targetStatus !== 'paid'
        ) {
            return;
        }

        if ($targetStatus === 'paid') {
            if (($session->payment_status ?? '') !== 'paid') {
                return;
            }

            $paymentIntent = $session->payment_intent ?? null;

            if (is_object($paymentIntent)) {
                $paymentIntent = $paymentIntent->id ?? null;
            }

            if (!is_string($paymentIntent) || $paymentIntent === '') {
                throw new \RuntimeException(
                    'Paid Stripe session has no payment intent.'
                );
            }

            // Save the successful Stripe payment first.
            $payment->update([
                'provider_payment_id' => $paymentIntent,
                'status' => 'paid',
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            // A payment arriving after cancellation must be refunded.
            if ($booking->status === \App\Enums\BookingStatus::Cancelled) {

                $existingRefund = DB::table('refunds')
                    ->where('payment_id', $payment->id)
                    ->where('reason', 'late_payment_after_booking_cancelled')
                    ->orderByDesc('id')
                    ->first();

                if ($existingRefund && $existingRefund->provider_refund_id) {
                    if ($existingRefund->status === 'succeeded') {
                        $payment->update(['status' => 'refunded']);
                    }

                    return;
                }

                if ($existingRefund) {
                    $refundId = $existingRefund->id;
                } else {
                    $refundId = DB::table('refunds')->insertGetId([
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


                // Refund will be processed by stripe:reconcile after this transaction commits.

                // Keep the booking cancelled.
                return;
            }

            if (
                !in_array($booking->status, [
                    \App\Enums\BookingStatus::PendingPayment,
                    \App\Enums\BookingStatus::Confirmed,
                ], true)
            ) {
                throw new \RuntimeException(
                    'Booking is not in a payable state.'
                );
            }

            if (!$booking->hold_id) {
                throw new \RuntimeException(
                    'Booking has no associated seat hold.'
                );
            }

            // Get the exact seats purchased by this booking.
            $bookingSeatIds = DB::table('booking_seats')
                ->where('booking_id', $booking->id)
                ->orderBy('seat_id')
                ->pluck('seat_id')
                ->map(fn($id) => (int) $id)
                ->all();

            if (empty($bookingSeatIds)) {
                throw new \RuntimeException(
                    'Booking has no allocated booking seats.'
                );
            }

            // Lock and verify the associated hold.
            $hold = DB::table('holds')
                ->where('id', $booking->hold_id)
                ->where('user_id', $booking->user_id)
                ->where('showtime_id', $booking->showtime_id)
                ->lockForUpdate()
                ->first();

            if (!$hold) {
                throw new \RuntimeException(
                    'Booking hold could not be verified.'
                );
            }

            // Lock the showtime-seat rows for the purchased seats.
            $showtimeSeats = DB::table('showtime_seats')
                ->where('showtime_id', $booking->showtime_id)
                ->whereIn('seat_id', $bookingSeatIds)
                ->orderBy('seat_id')
                ->lockForUpdate()
                ->get();

            $actualSeatIds = $showtimeSeats
                ->pluck('seat_id')
                ->map(fn($id) => (int) $id)
                ->all();

            if (
                count($actualSeatIds) !== count($bookingSeatIds) ||
                $actualSeatIds !== $bookingSeatIds
            ) {
                throw new \RuntimeException(
                    'Booking seats do not match showtime-seat allocations.'
                );
            }

            // Each seat must either already belong to this booking
            // or still be held by this booking's hold.
            foreach ($showtimeSeats as $seat) {
                $alreadySoldToBooking =
                    $seat->status === 'sold' &&
                    (int) $seat->booking_id === (int) $booking->id;

                $stillHeldForBooking =
                    $seat->status === 'held' &&
                    (int) $seat->hold_id === (int) $booking->hold_id &&
                    $seat->held_until !== null &&
                    \Carbon\Carbon::parse($seat->held_until)->isFuture();

                if (!$alreadySoldToBooking && !$stillHeldForBooking) {
                    throw new \RuntimeException(
                        'A purchased seat is no longer held by this booking.'
                    );
                }
            }

            $heldSeatIds = $showtimeSeats
                ->filter(fn($seat) => $seat->status === 'held')
                ->pluck('id')
                ->all();

            // Only convert seats if the hold is still active and valid.
            if (!empty($heldSeatIds)) {
                if (
                    $hold->status !== 'active' ||
                    !\Carbon\Carbon::parse($hold->expires_at)->isFuture()
                ) {
                    throw new \RuntimeException(
                        'The seat hold has expired or is no longer active.'
                    );
                }

                DB::table('showtime_seats')
                    ->whereIn('id', $heldSeatIds)
                    ->where('status', 'held')
                    ->where('hold_id', $booking->hold_id)
                    ->update([
                        'status' => 'sold',
                        'booking_id' => $booking->id,
                        'hold_id' => null,
                        'held_until' => null,
                        'updated_at' => now(),
                    ]);
            }

            // Convert the temporary hold into a completed hold.
            DB::table('holds')
                ->where('id', $booking->hold_id)
                ->update([
                    'status' => 'converted',
                    'updated_at' => now(),
                ]);

            // Confirm only after the seat allocations have been verified.
            if (
                $booking->status ===
                \App\Enums\BookingStatus::PendingPayment
            ) {
                $booking->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);
            }

            // This action is idempotent for already-issued tickets.
            $this->issueTickets->handle($booking->refresh());
            return;
        }

        if ($payment->status === \App\Enums\PaymentStatus::Pending) {
            $payment->update([
                'status' => $targetStatus,
            ]);

            if (
                in_array($targetStatus, ['expired', 'failed'], true) &&
                $booking->status === \App\Enums\BookingStatus::PendingPayment
            ) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                if ($booking->hold_id) {
                    DB::table('holds')
                        ->where('id', $booking->hold_id)
                        ->where('status', 'active')
                        ->update([
                            'status' => 'expired',
                            'updated_at' => now(),
                        ]);

                    DB::table('showtime_seats')
                        ->where('hold_id', $booking->hold_id)
                        ->where('status', 'held')
                        ->update([
                            'status' => 'available',
                            'hold_id' => null,
                            'booking_id' => null,
                            'held_until' => null,
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }
    private function processFailedPaymentIntent(object $intent): void
    {
        $intentId = $intent->id ?? null;
        $bookingId = $intent->metadata->booking_id ?? null;
        $bookingReference = $intent->metadata->booking_reference ?? null;

        if (
            !is_string($intentId) ||
            !is_string($bookingId) ||
            !ctype_digit($bookingId) ||
            !is_string($bookingReference) ||
            trim($bookingReference) === ''
        ) {
            throw new \RuntimeException(
                'Invalid failed PaymentIntent metadata.'
            );
        }

        $booking = Booking::query()
            ->whereKey((int) $bookingId)
            ->lockForUpdate()
            ->first();

        if (
            !$booking ||
            (string) $booking->booking_reference !== $bookingReference
        ) {
            throw new \RuntimeException(
                'Failed PaymentIntent booking does not match.'
            );
        }

        $payment = Payment::query()
            ->where('booking_id', $booking->id)
            ->where('provider', 'stripe')
            ->lockForUpdate()
            ->first();

        if (!$payment) {
            throw new \RuntimeException(
                'No local payment matches this failed PaymentIntent.'
            );
        }

        if (
            (int) ($intent->amount ?? -1) !== (int) $payment->amount_cents ||
            strtolower((string) ($intent->currency ?? '')) !==
            strtolower((string) $payment->currency)
        ) {
            throw new \RuntimeException(
                'Failed PaymentIntent amount or currency does not match.'
            );
        }

        if (
            in_array($payment->status, [
                \App\Enums\PaymentStatus::Paid,
                \App\Enums\PaymentStatus::Refunded,
            ], true)
        ) {
            return;
        }

        if ($payment->status !== \App\Enums\PaymentStatus::Pending) {
            return;
        }

        $payment->update([
            'provider_payment_id' => $intentId,
            'status' => 'failed',
        ]);

        if (
            $booking->status === \App\Enums\BookingStatus::PendingPayment
        ) {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            if ($booking->hold_id) {
                DB::table('holds')
                    ->where('id', $booking->hold_id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'expired',
                        'updated_at' => now(),
                    ]);

                DB::table('showtime_seats')
                    ->where('hold_id', $booking->hold_id)
                    ->where('status', 'held')
                    ->update([
                        'status' => 'available',
                        'hold_id' => null,
                        'booking_id' => null,
                        'held_until' => null,
                        'updated_at' => now(),
                    ]);
            }
        }
    }
    private function processRefund(object $refund): void
    {
        $refundId = $refund->id ?? null;
        $paymentIntent = $refund->payment_intent ?? null;

        if (is_object($paymentIntent)) {
            $paymentIntent = $paymentIntent->id ?? null;
        }

        if (
            !is_string($refundId) ||
            !is_string($paymentIntent) ||
            !is_numeric($refund->amount ?? null)
        ) {
            throw new \RuntimeException('Invalid Stripe refund data.');
        }

        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where('provider_payment_id', $paymentIntent)
            ->lockForUpdate()
            ->first();

        if (!$payment) {
            throw new \RuntimeException(
                'No local payment matches this Stripe refund.'
            );
        }

        $refundStatus = match ((string) ($refund->status ?? 'pending')) {
            'succeeded' => 'succeeded',
            'failed' => 'failed',
            'canceled' => 'canceled',
            default => 'pending',
        };


        $existing = DB::table('refunds')
            ->where('provider_refund_id', $refundId)
            ->first();

        // First, try the exact local refund ID sent through Stripe metadata.
        $metadataRefundId = $refund->metadata->local_refund_id ?? null;

        if (!$existing && $metadataRefundId !== null) {
            if (!ctype_digit((string) $metadataRefundId)) {
                throw new \RuntimeException(
                    'Stripe refund contains an invalid local refund ID.'
                );
            }

            $existing = DB::table('refunds')
                ->where('id', (int) $metadataRefundId)
                ->first();

            if (!$existing) {
                throw new \RuntimeException(
                    'Stripe refund metadata references a missing local refund.'
                );
            }

            if (
                (int) $existing->payment_id !== (int) $payment->id ||
                (int) $existing->amount_cents !== (int) $refund->amount
            ) {
                throw new \RuntimeException(
                    'Stripe refund metadata does not match the local payment or amount.'
                );
            }

            if (
                $existing->provider_refund_id !== null &&
                $existing->provider_refund_id !== $refundId
            ) {
                throw new \RuntimeException(
                    'Local refund is already linked to a different Stripe refund.'
                );
            }
        }

        // Preserve the existing matching behavior for older refunds
// that do not contain local_refund_id metadata.
        if (!$existing && $metadataRefundId === null) {
            $matchingRefunds = DB::table('refunds')
                ->where('payment_id', $payment->id)
                ->whereNull('provider_refund_id')
                ->where('amount_cents', (int) $refund->amount)
                ->where('status', 'pending')
                ->orderByDesc('id')
                ->get();

            if ($matchingRefunds->count() > 1) {
                throw new \RuntimeException(
                    'Multiple local pending refunds match this Stripe refund; '
                    . 'refusing ambiguous match.'
                );
            }

            $existing = $matchingRefunds->first();
        }

        if (
            $existing &&
            (int) $existing->payment_id !== (int) $payment->id
        ) {
            throw new \RuntimeException(
                'Stripe refund ID is already linked to a different local payment.'
            );
        }
        $createdTimestamp = is_numeric($refund->created ?? null)
            ? (int) $refund->created
            : time();

        $values = [
            'provider_refund_id' => $refundId,
            'status' => $refundStatus,
            'reason' => $refund->reason ?? ($existing->reason ?? null),
            'requested_at' => $existing->requested_at
                ?? Carbon::createFromTimestamp(
                    $createdTimestamp
                )->toDateTimeString(),
            'completed_at' => in_array(
                $refundStatus,
                ['succeeded', 'failed', 'canceled'],
                true
            ) ? now() : null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('refunds')
                ->where('id', $existing->id)
                ->update($values);
        } else {
            DB::table('refunds')->insert(array_merge($values, [
                'payment_id' => $payment->id,
                'amount_cents' => (int) $refund->amount,
                'created_at' => now(),
            ]));
        }

        $successfulRefundTotal = (int) DB::table('refunds')
            ->where('payment_id', $payment->id)
            ->where('status', 'succeeded')
            ->sum('amount_cents');

        if ($successfulRefundTotal >= (int) $payment->amount_cents) {
            $payment->update([
                'status' => 'refunded',
            ]);

            Booking::query()
                ->whereKey($payment->booking_id)
                ->where('status', 'confirmed')
                ->update([
                    'status' => 'refunded',
                    'updated_at' => now(),
                ]);
        }
    }
}
