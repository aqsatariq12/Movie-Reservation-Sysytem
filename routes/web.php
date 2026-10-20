<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');

Route::get('/movies/{movie}', [MovieController::class, 'show'])
    ->name('movies.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/my-bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/my-bookings/{booking}/cancel', [BookingController::class, 'confirmCancel'])->name('bookings.cancel.confirm');
    Route::get('/my-bookings/{booking}/cancelled', [BookingController::class, 'cancelled'])->name('bookings.cancelled');
    // Logic phase: Route::post('/my-bookings/{booking}/cancel', ...)->name('bookings.cancel');

});

require __DIR__ . '/settings.php';