<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hold;
use App\Models\Payment;
use App\Services\StripePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Throwable;

class StripePaymentController extends Controller
{
    public function releaseHold(
        Request $request,
        Booking $booking
    ): RedirectResponse {
        abort_unless(
            (int) $booking->user_id === (int) $request->user()->id,
            403
        );

        if ($booking->status !== \App\Enums\BookingStatus::PendingPayment) {
            if (
                $booking->status === \App\Enums\BookingStatus::Cancelled &&
                $booking->hold_id &&
                Hold::whereKey($booking->hold_id)
                    ->where('status', 'released')
                    ->exists()
            ) {
                return back()->with(
                    'status',
                    'This booking hold has already been released successfully.'
                );
            }

            return back()->with(
                'error',
                'This booking can no longer be released.'
            );
        }

        $hasPaidPayment = Payment::query()
            ->where('booking_id', $booking->id)
            ->whereIn('status', ['paid', 'refunded'])
            ->exists();

        if ($hasPaidPayment) {
            return back()->with(
                'error',
                'A paid or refunded booking cannot be released.'
            );
        }

        DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBooking->status !== \App\Enums\BookingStatus::PendingPayment) {
                return;
            }

            DB::table('showtime_seats')
                ->where('booking_id', $lockedBooking->id)
                ->where('status', 'held')
                ->update([
                    'status' => 'available',
                    'hold_id' => null,
                    'booking_id' => null,
                    'held_until' => null,
                    'updated_at' => now(),
                ]);

            if ($lockedBooking->hold_id) {
                Hold::query()
                    ->whereKey($lockedBooking->hold_id)
                    ->where('user_id', $lockedBooking->user_id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'released',
                        'updated_at' => now(),
                    ]);
            }

            $lockedBooking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        });

        return back()->with('status', 'Hold released successfully.');
    }

    public function checkout(
        Request $request,
        Booking $booking,
        StripePaymentService $stripePaymentService
    ): RedirectResponse {
        abort_unless(
            (int) $booking->user_id === (int) $request->user()->id,
            403
        );

        abort_unless(
            $booking->status === \App\Enums\BookingStatus::PendingPayment,
            403,
            'This booking cannot be paid.'
        );

        $payment = Payment::query()
            ->where('booking_id', $booking->id)
            ->where('provider', 'stripe')
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($payment && $payment->provider_session_id) {
            try {
                $existingSession = $stripePaymentService
                    ->retrieveCheckoutSession(
                        $payment->provider_session_id
                    );

                if (
                    $existingSession->status === 'open' &&
                    $existingSession->url
                ) {
                    return redirect()->away($existingSession->url);
                }

                if ($existingSession->payment_status === 'paid') {
                    return redirect()->route('payment.success', [
                        'session_id' => $existingSession->id,
                    ]);
                }

                if ($existingSession->status === 'complete') {
                    return redirect()->route('payment.failed', [
                        'booking_id' => $booking->id,
                    ])->with(
                        'error',
                        'Payment has not been confirmed. Please check your booking status.'
                    );
                }

                if ($existingSession->status === 'expired') {
                    // Old session expired. Allow a new session attempt.
                    $payment->provider_session_id = null;
                    $payment->save();
                }
            } catch (ApiErrorException $exception) {
                if ($exception->getStripeCode() === 'resource_missing') {
                    // Session belongs to an old/different Stripe test account.
                    $payment->provider_session_id = null;
                    $payment->save();
                } else {
                    report($exception);

                    return back()->with(
                        'error',
                        'Unable to verify the existing Stripe session. Please try again.'
                    );
                }
            }
        }

        try {
            $session = $stripePaymentService
                ->createCheckoutSession($booking);
        } catch (ApiErrorException $exception) {
            report($exception);

            return back()->with(
                'error',
                'Stripe could not create a Checkout Session. Please try again.'
            );
        }

        try {
            DB::transaction(function () use (
                $booking,
                $session,
                &$payment
            ) {
                $lockedBooking = Booking::query()
                    ->whereKey($booking->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedBooking->status !== \App\Enums\BookingStatus::PendingPayment) {
                    throw new \RuntimeException(
                        'Booking is no longer eligible for payment.'
                    );
                }

                if (!$payment) {
                    $payment = new Payment();
                    $payment->booking_id = $lockedBooking->id;
                    $payment->provider = 'stripe';
                    $payment->status = 'pending';
                }

                $payment->provider_session_id = $session->id;
                $payment->amount_cents = $lockedBooking->total_amount_cents;
                $payment->currency = $lockedBooking->currency;
                $payment->save();
            });
        } catch (Throwable $exception) {
            report($exception);

            // Prevent an untracked Checkout Session from remaining open.
            try {
                $stripePaymentService->expireCheckoutSession($session->id);
            } catch (Throwable $expireException) {
                report($expireException);
            }

            return back()->with(
                'error',
                'Checkout was created, but its payment record could not be saved. Please try again.'
            );
        }

        return redirect()->away($session->url);
    }

    public function success(
        Request $request,
        StripePaymentService $stripePaymentService
    ): View {
        $sessionId = $request->query('session_id');

        abort_unless(
            is_string($sessionId) && $sessionId !== '',
            400,
            'Missing Stripe session ID.'
        );

        try {
            $session = $stripePaymentService
                ->retrieveCheckoutSession($sessionId);
        } catch (ApiErrorException $exception) {
            report($exception);

            abort(
                502,
                'Unable to verify payment with Stripe. Please try again.'
            );
        }

        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where('provider_session_id', $session->id)
            ->firstOrFail();

        abort_unless(
            (string) ($session->metadata->booking_id ?? '') ===
                (string) $payment->booking_id,
            403
        );

        $booking = Booking::query()
            ->whereKey($payment->booking_id)
            ->firstOrFail();

        abort_unless(
            (int) $booking->user_id === (int) $request->user()->id,
            403
        );

        abort_unless(
            (string) ($session->metadata->booking_reference ?? '') ===
                (string) $booking->booking_reference,
            403
        );

        $payment = $payment->fresh();
        $booking = $booking->fresh();

        $isPaid = $session->payment_status === 'paid'
            && $payment->status === \App\Enums\PaymentStatus::Paid
            && $booking->status === \App\Enums\BookingStatus::Confirmed;

        return view('payment.success', [
            'payment' => $payment,
            'booking' => $booking->load(
                'showtime.movie',
                'showtime.screen'
            ),
            'isPaid' => $isPaid,
        ]);
    }

    public function cancel(Request $request): View
    {
        $booking = $this->findOwnedBooking($request);

        $payment = Payment::query()
            ->where('booking_id', $booking->id)
            ->where('provider', 'stripe')
            ->latest('id')
            ->first();

        return view('payment.cancel', [
            'booking' => $booking->load(
                'showtime.movie',
                'showtime.screen'
            ),
            'payment' => $payment,
        ]);
    }

    public function failed(Request $request): View
    {
        $booking = $this->findOwnedBooking($request);

        $payment = Payment::query()
            ->where('booking_id', $booking->id)
            ->where('provider', 'stripe')
            ->where('status', 'failed')
            ->latest('id')
            ->firstOrFail();

        return view('payment.failed', [
            'booking' => $booking->load(
                'showtime.movie',
                'showtime.screen'
            ),
            'payment' => $payment,
        ]);
    }

    public function expired(Request $request): View
    {
        $booking = $this->findOwnedBooking($request);

        $payment = Payment::query()
            ->where('booking_id', $booking->id)
            ->where('provider', 'stripe')
            ->where('status', 'expired')
            ->latest('id')
            ->firstOrFail();

        return view('payment.expired', [
            'booking' => $booking->load(
                'showtime.movie',
                'showtime.screen'
            ),
            'payment' => $payment,
        ]);
    }

    public function lateRefund(Request $request): View
    {
        $booking = $this->findOwnedBooking($request);

        $payment = Payment::query()
            ->where('booking_id', $booking->id)
            ->where('provider', 'stripe')
            ->latest('id')
            ->firstOrFail();

        $refunds = DB::table('refunds')
            ->where('payment_id', $payment->id)
            ->orderByDesc('id')
            ->get();

        abort_unless(
            $refunds->isNotEmpty(),
            404,
            'No refund record found.'
        );

        return view('payment.late-refund', [
            'booking' => $booking->load(
                'showtime.movie',
                'showtime.screen'
            ),
            'payment' => $payment,
            'refunds' => $refunds,
        ]);
    }

    private function findOwnedBooking(Request $request): Booking
    {
        $bookingId = $request->query('booking_id');

        abort_unless(
            is_string($bookingId) && ctype_digit($bookingId),
            400,
            'Missing booking ID.'
        );

        return Booking::query()
            ->whereKey((int) $bookingId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }
}
