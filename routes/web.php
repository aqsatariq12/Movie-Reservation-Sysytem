<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Customer\SeatMapController;
use App\Http\Controllers\Customer\HoldController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ScreenController;
use App\Http\Controllers\Admin\ShowtimeController;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', function () {
    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');

Route::get('/movies/{movie}', [MovieController::class, 'show'])
    ->name('movies.show');

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::get('/my-bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/my-bookings/{booking}/cancel', [BookingController::class, 'confirmCancel'])->name('bookings.cancel.confirm');
    Route::get('/my-bookings/{booking}/cancelled', [BookingController::class, 'cancelled'])->name('bookings.cancelled');
    // Logic phase: Route::post('/my-bookings/{booking}/cancel', ...)->name('bookings.cancel');

    // Seat selection and seat holds
    Route::get('/showtimes/{showtime}/seats', [SeatMapController::class, 'show'])
        ->name('showtimes.seats');

    Route::post('/showtimes/{showtime}/holds', [HoldController::class, 'store'])
        ->name('holds.store');

    Route::get('/holds/{hold}', [HoldController::class, 'show'])
        ->name('holds.show');

    Route::delete('/holds/{hold}', [HoldController::class, 'destroy'])
        ->name('holds.destroy');

});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard', [
                'moviesCount' => \App\Models\Movie::count(),
                'screensCount' => \App\Models\Screen::count(),
                'showtimesCount' => \App\Models\Showtime::count(),
                'bookingsCount' => \App\Models\Booking::count(),
            ]);
        })->name('dashboard');

        // Screen management
        Route::resource('screens', ScreenController::class)
            ->except(['edit']);

        Route::post('/screens/{screen}/sync-seats', [ScreenController::class, 'syncSeats'])
            ->name('screens.sync-seats');

        // Showtime management
        Route::resource('showtimes', ShowtimeController::class)
            ->except(['show', 'destroy']);
    });
require __DIR__ . '/settings.php';