
<?php

use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\ScreenController;
use App\Http\Controllers\Admin\ShowtimeController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Customer\HoldController;
use App\Http\Controllers\Customer\SeatMapController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Admin\TicketScannerController;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', function () {
    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

// Public movie catalogue
Route::get('/movies', [MovieController::class, 'index'])
    ->name('movies.index');

Route::get('/movies/{movie}', [MovieController::class, 'show'])
    ->name('movies.show');

// Customer bookings, seat selection and holds
Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::get('/my-bookings', [BookingController::class, 'index'])
        ->name('bookings.index');

    Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])
        ->name('bookings.show');

    Route::get('/my-bookings/{booking}/cancel', [BookingController::class, 'confirmCancel'])
        ->name('bookings.cancel.confirm');

    Route::get('/my-bookings/{booking}/cancelled', [BookingController::class, 'cancelled'])
        ->name('bookings.cancelled');

    Route::get('/showtimes/{showtime}/seats', [SeatMapController::class, 'show'])
        ->name('showtimes.seats');

    Route::post('/showtimes/{showtime}/holds', [HoldController::class, 'store'])
        ->name('holds.store');

    Route::get('/holds/{hold}', [HoldController::class, 'show'])
        ->name('holds.show');

    Route::delete('/holds/{hold}', [HoldController::class, 'destroy'])
        ->name('holds.destroy');
});

// Stripe Checkout and payment pages
Route::middleware('auth')->group(function () {
    Route::post('/bookings/{booking}/checkout', [StripePaymentController::class, 'checkout'])
        ->name('payment.checkout');

    Route::get('/payment/success', [StripePaymentController::class, 'success'])
        ->name('payment.success');

    Route::get('/payment/cancel', [StripePaymentController::class, 'cancel'])
        ->name('payment.cancel');

    Route::get('/payment/failed', [StripePaymentController::class, 'failed'])
        ->name('payment.failed');

    Route::get('/payment/expired', [StripePaymentController::class, 'expired'])
        ->name('payment.expired');

    Route::get('/payment/late-refund', [StripePaymentController::class, 'lateRefund'])
        ->name('payment.late-refund');

    Route::get('/bookings/{booking}/recovery-states', function (
        Request $request,
        Booking $booking
    ) {
        abort_unless(
            (int) $booking->user_id === (int) $request->user()->id,
            403
        );

        return view('bookings.recovery-states', [
            'booking' => $booking->load(['payments', 'hold']),
        ]);
    })->name('booking.recovery-states');

    Route::post('/bookings/{booking}/release-hold', [StripePaymentController::class, 'releaseHold'])
        ->name('booking.release-hold');
    
        // Customer tickets
    Route::get('/bookings/{booking}/tickets', [TicketController::class, 'show'])
        ->name('booking.tickets');

    Route::get('/booking/success', function () {
        return view('bookings.success');
    })->name('booking.success');
});

// Stripe webhook must be accessible without login.
// CSRF exemption is configured in bootstrap/app.php.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');

// Admin catalogue management
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard', [
                'moviesCount' => \App\Models\Movie::count(),
                'screensCount' => \App\Models\Screen::count(),
                'showtimesCount' => \App\Models\Showtime::count(),
                'bookingsCount' => Booking::count(),
            ]);
        })->name('dashboard');

        Route::resource('screens', ScreenController::class)
            ->except(['edit']);

        Route::resource('movies', AdminMovieController::class)
            ->except(['show', 'destroy']);

        Route::patch('/movies/{movie}/archive', [AdminMovieController::class, 'archive'])
            ->name('movies.archive');

        Route::resource('genres', GenreController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('/screens/{screen}/sync-seats', [ScreenController::class, 'syncSeats'])
            ->name('screens.sync-seats');

        Route::resource('showtimes', ShowtimeController::class)
            ->except(['show', 'destroy']);
            Route::get('/ticket-scanner', [TicketScannerController::class, 'index'])
    ->name('ticket-scanner');

Route::post('/ticket-scanner/scan', [TicketScannerController::class, 'scan'])
    ->name('ticket-scanner.scan');
    });

require __DIR__ . '/settings.php';
