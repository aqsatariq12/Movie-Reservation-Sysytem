<?php

use App\Http\Controllers\Admin\ScreenController;
use App\Http\Controllers\Admin\ShowtimeController;
use App\Http\Controllers\Customer\HoldController;
use App\Http\Controllers\Customer\SeatMapController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__ . '/settings.php';

// TODO: add the owner's role middleware, e.g. ['auth', 'verified', 'role:admin'], once merged
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('screens', ScreenController::class)->except(['edit']);
    Route::put('screens/{screen}/seats', [ScreenController::class, 'syncSeats'])
        ->name('screens.seats.sync');

    Route::resource('showtimes', ShowtimeController::class)->only(['index', 'create', 'store', 'edit', 'update']);});

// Customer: the seat map is public, holding seats needs a verified account
Route::get('showtimes/{showtime}/seats', [SeatMapController::class, 'show'])
    ->name('showtimes.seats');

Route::middleware(['auth', 'verified', 'throttle:10,1'])->group(function () {
    Route::post('showtimes/{showtime}/holds', [HoldController::class, 'store'])
        ->name('showtimes.holds.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('holds/{hold}', [HoldController::class, 'show'])->name('holds.show');
    Route::delete('holds/{hold}', [HoldController::class, 'destroy'])->name('holds.destroy');
});