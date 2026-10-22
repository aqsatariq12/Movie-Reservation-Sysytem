<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\StripePaymentController;
use App\Models\Booking;
use Illuminate\Http\Request;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware('auth')->group(function () {
    // Stripe Checkout
    Route::post(
        '/bookings/{booking}/checkout',
        [StripePaymentController::class, 'checkout']
    )->name('payment.checkout');

    // Payment success
    Route::get(
        '/payment/success',
        [StripePaymentController::class, 'success']
    )->name('payment.success');

    // Payment cancelled
    Route::get(
        '/payment/cancel',
        [StripePaymentController::class, 'cancel']
    )->name('payment.cancel');

    // Payment failed
    Route::get(
        '/payment/failed',
        [StripePaymentController::class, 'failed']
    )->name('payment.failed');

    // Payment expired
    Route::get(
        '/payment/expired',
        [StripePaymentController::class, 'expired']
    )->name('payment.expired');

    // Late payment / refund
    Route::get(
        '/payment/late-refund',
        [StripePaymentController::class, 'lateRefund']
    )->name('payment.late-refund');

    // Booking recovery states
    Route::get(
        '/bookings/{booking}/recovery-states',
        function (Request $request, Booking $booking) {
            abort_unless(
                (int) $booking->user_id === (int) $request->user()->id,
                403
            );

            return view('bookings.recovery-states', [
                'booking' => $booking->load(['payments', 'hold']),
            ]);
        }
    )->name('booking.recovery-states');

    // Release booking hold
    Route::post(
        '/bookings/{booking}/release-hold',
        [StripePaymentController::class, 'releaseHold']
    )->name('booking.release-hold');

    // Existing booking success page
    Route::get('/booking/success', function () {
        return view('bookings.success');
    })->name('booking.success');
});

// Stripe webhook (Stripe must access this without login)
Route::post(
    '/stripe/webhook',
    [StripeWebhookController::class, 'handle']
)->name('stripe.webhook');

require __DIR__ . '/settings.php';
