<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\StripePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class StripePaymentController extends Controller
{
    public function releaseHold(Request $request, Booking $booking): RedirectResponse
{
    abort_unless(
        (int) $booking->user_id === (int) $request->user()->id,
        403
    );

    if ($booking->status !== 'pending_payment') {
    if (
        $booking->status === 'cancelled' &&
        $booking->hold_id &&
        \App\Models\Hold::whereKey($booking->hold_id)
            ->where('status', 'released')
            ->exists()
    ) {
        return back()->with('status', 'This booking hold has already been released successfully.');
    }

    return back()->with('error', 'This booking can no longer be released.');
}

    $hasPaidPayment = Payment::where('booking_id', $booking->id)
        ->where('status', 'paid')
        ->exists();

    if ($hasPaidPayment) {
        return back()->with('error', 'A paid booking cannot be released.');
    }

    \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
        $lockedBooking = Booking::whereKey($booking->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($lockedBooking->status !== 'pending_payment') {
            return;
        }

        \Illuminate\Support\Facades\DB::table('showtime_seats')
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
            \App\Models\Hold::whereKey($lockedBooking->hold_id)
                ->where('user_id', $lockedBooking->user_id)
                ->where('status', 'active')
                ->update(['status' => 'released']);
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
            $booking->user_id === $request->user()->id,
            403
        );

        abort_unless(
            $booking->status === 'pending_payment',
            403,
            'This booking cannot be paid.'
        );

        $session = $stripePaymentService->createCheckoutSession($booking);

        Payment::updateOrCreate(
            [
                'booking_id' => $booking->id,
                'provider' => 'stripe',
                'status' => 'pending',
            ],
            [
                'provider_session_id' => $session->id,
                'amount_cents' => $booking->total_amount_cents,
                'currency' => $booking->currency,
            ]
        );

        return redirect()->away($session->url);
    }

    public function success(
        Request $request,
        StripePaymentService $stripePaymentService
    ): View {
        $sessionId = $request->query('session_id');

        if (! is_string($sessionId) || $sessionId === '') {
            abort(400, 'Missing Stripe session ID.');
        }

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

        $payment = Payment::where('provider_session_id', $session->id)
            ->where('provider', 'stripe')
            ->firstOrFail();

        abort_unless(
            (string) ($session->metadata->booking_id ?? '') ===
                (string) $payment->booking_id,
            403
        );

        return view('payment.success', [
            'payment' => $payment->fresh(),
            'booking' => $payment->booking()->first(),
            'isPaid' => $session->payment_status === 'paid',
        ]);
    }

    public function cancel(): View
    {
        return view('payment.cancel');
    }

    public function failed(): View
    {
        return view('payment.failed');
    }

    public function expired(): View
    {
        return view('payment.expired');
    }

    public function lateRefund(): View
    {
        return view('payment.late-refund');
    }
}
