
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\StripePaymentController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::post(
        '/bookings/{booking}/checkout',
        [StripePaymentController::class, 'checkout']
    )->name('payment.checkout');

    Route::get(
        '/payment/success',
        [StripePaymentController::class, 'success']
    )->name('payment.success');

    Route::get(
        '/payment/cancel',
        [StripePaymentController::class, 'cancel']
    )->name('payment.cancel');
});
Route::post(
    '/stripe/webhook',
    [StripeWebhookController::class, 'handle']
)->name('stripe.webhook');
// 1. Single 2x2 Grid Overview (Figma Style)
Route::get('/bookings/recovery-states', function () {
    return view('bookings.recovery-states');
});
Route::post(
    '/bookings/{booking}/release-hold',
    [StripePaymentController::class, 'releaseHold']
)->name('booking.release-hold');
// 2. Individual States Routes
Route::get(
    '/payment/failed',
    [StripePaymentController::class, 'failed']
)->name('payment.failed');

Route::get(
    '/payment/expired',
    [StripePaymentController::class, 'expired']
)->name('payment.expired');

Route::get(
    '/payment/late-refund',
    [StripePaymentController::class, 'lateRefund']
)->name('payment.late-refund');


Route::get('/booking/success', function () {
    return view('bookings.success');
});
require __DIR__.'/settings.php';
