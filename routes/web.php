<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Customer\SeatMapController;
use App\Http\Controllers\Customer\HoldController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ScreenController;
use App\Http\Controllers\Admin\ShowtimeController;
use App\Http\Controllers\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\FailedJobController;
use App\Http\Controllers\Admin\AuditLogController;

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

        // Movie management
        Route::resource('movies', AdminMovieController::class)
            ->except(['show', 'destroy']);

        // Customer directory (records are retained: no create/edit/delete)
        Route::resource('customers', CustomerController::class)
            ->only(['index', 'show']);

        Route::patch('/movies/{movie}/archive', [AdminMovieController::class, 'archive'])
            ->name('movies.archive');
        // Genre management (create/edit/delete states live on the index page)
        Route::resource('genres', GenreController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('/screens/{screen}/sync-seats', [ScreenController::class, 'syncSeats'])
            ->name('screens.sync-seats');

        // Showtime management
        Route::resource('showtimes', ShowtimeController::class)
            ->except(['show', 'destroy']);

        Route::get('/showtimes/{showtime}/cancel', [ShowtimeController::class, 'confirmCancel'])
            ->name('showtimes.cancel');
        Route::post('/showtimes/{showtime}/cancel', [ShowtimeController::class, 'cancel'])
            ->name('showtimes.cancel.store');

        // Staff & admin role management
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::patch('/staff/{user}/role', [StaffController::class, 'updateRole'])->name('staff.role.update');

        // Reservation settings
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Failed background jobs
        Route::get('/failed-jobs', [FailedJobController::class, 'index'])->name('failed-jobs.index');
        Route::get('/failed-jobs/{uuid}', [FailedJobController::class, 'show'])->whereUuid('uuid')->name('failed-jobs.show');
        Route::post('/failed-jobs/{uuid}/retry', [FailedJobController::class, 'retry'])->whereUuid('uuid')->name('failed-jobs.retry');
        Route::delete('/failed-jobs/{uuid}', [FailedJobController::class, 'destroy'])->whereUuid('uuid')->name('failed-jobs.destroy');


        // Audit log
        // Audit log (read-only)
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.index');
    });
// TEMP: preview error pages. Remove when done.
require __DIR__ . '/settings.php';