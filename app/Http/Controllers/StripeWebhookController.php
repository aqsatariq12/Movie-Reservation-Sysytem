<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('stripe.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            Log::error('Stripe webhook secret is not configured.');

            return response()->json([
                'error' => 'Webhook is not configured.',
            ], 500);
        }

        if (! is_string($signature) || $signature === '') {
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
        } catch (
            UnexpectedValueException |
            SignatureVerificationException $exception
        ) {
            Log::warning('Stripe webhook signature/payload rejected.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'error' => 'Invalid webhook payload or signature.',
            ], 400);
        }

        if (! in_array($event->type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
        ], true)) {
            return response()->json([
                'received' => true,
                'ignored' => true,
            ]);
        }

        $session = $event->data->object;

        if (($session->payment_status ?? '') !== 'paid') {
            return response()->json([
                'received' => true,
                'payment' => 'not_paid',
            ]);
        }

        $sessionId = $session->id ?? null;
        $bookingId = $session->metadata->booking_id ?? null;
        $bookingReference = $session->metadata->booking_reference ?? null;

        if (
            ! is_string($sessionId) ||
            ! is_string($bookingId) ||
            ! ctype_digit($bookingId) ||
            ! is_string($bookingReference) ||
            $bookingReference === ''
        ) {
            Log::warning('Stripe webhook metadata rejected.', [
                'session_id_present' => is_string($sessionId),
                'booking_id_type' => gettype($bookingId),
                'booking_reference_type' => gettype($bookingReference),
            ]);

            return response()->json([
                'error' => 'Invalid booking metadata.',
            ], 400);
        }

        try {
            DB::transaction(function () use (
                $event,
                $session,
                $sessionId,
                $bookingId,
                $bookingReference
            ): void {
                DB::table('webhook_events')->insert([
                    'provider_event_id' => $event->id,
                    'event_type' => $event->type,
                    'status' => 'processing',
                    'booking_id' => (int) $bookingId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $payment = Payment::query()
                    ->where('provider', 'stripe')
                    ->where('provider_session_id', $sessionId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $booking = Booking::query()
                    ->whereKey($payment->booking_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    (string) $booking->id !== $bookingId ||
                    (string) $booking->id !== (string) $payment->booking_id ||
                    (string) $booking->booking_reference !== $bookingReference
                ) {
                    throw new \RuntimeException(
                        'Stripe booking metadata does not match.'
                    );
                }

                if (
                    (int) ($session->amount_total ?? -1) !==
                        (int) $payment->amount_cents ||
                    strtolower((string) ($session->currency ?? '')) !==
                        strtolower((string) $payment->currency)
                ) {
                    throw new \RuntimeException(
                        'Stripe payment amount or currency does not match.'
                    );
                }

                if (! in_array($booking->status, [
                    'pending_payment',
                    'confirmed',
                ], true)) {
                    throw new \RuntimeException(
                        'Booking is not in a payable state.'
                    );
                }

                $paymentIntent = $session->payment_intent ?? null;

                if (is_object($paymentIntent)) {
                    $paymentIntent = $paymentIntent->id ?? null;
                }

                $payment->update([
                    'provider_payment_id' => is_string($paymentIntent)
                        ? $paymentIntent
                        : null,
                    'status' => 'paid',
                    'paid_at' => $payment->paid_at ?? now(),
                ]);

                if ($booking->status === 'pending_payment') {
                    $booking->update([
                        'status' => 'confirmed',
                        'confirmed_at' => now(),
                    ]);
                }

                DB::table('webhook_events')
                    ->where('provider_event_id', $event->id)
                    ->update([
                        'status' => 'processed',
                        'processed_at' => now(),
                        'updated_at' => now(),
                    ]);
            });

        } catch (\Illuminate\Database\QueryException $exception) {
            Log::error('Stripe webhook database failure.', [
                'message' => $exception->getMessage(),
            ]);

            if (
                DB::table('webhook_events')
                    ->where('provider_event_id', $event->id)
                    ->exists()
            ) {
                return response()->json([
                    'received' => true,
                    'duplicate' => true,
                ]);
            }

            report($exception);

            return response()->json([
                'error' => 'Webhook database operation failed.',
            ], 500);

        } catch (\Throwable $exception) {
            Log::error('Stripe webhook processing failure.', [
                'message' => $exception->getMessage(),
            ]);

            report($exception);

            return response()->json([
                'error' => 'Webhook processing failed.',
            ], 500);
        }

        return response()->json([
            'received' => true,
            'processed' => true,
        ]);
    }
}
