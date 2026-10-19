<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/movies', [                            MovieController::class, 'index'])->name('movies.index');

Route::get('/movies/{movie}', [MovieController::class, 'show'])
    ->name('movies.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('/my-bookings', [BookingController::class, 'index'])->name('bookings.index');
    // next screen: Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');

});

require __DIR__.'/settings.php';