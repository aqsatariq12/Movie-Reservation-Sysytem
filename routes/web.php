<?php

use App\Http\Controllers\Admin\ScreenController;
use App\Http\Controllers\Admin\ShowtimeController;
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

    Route::resource('showtimes', ShowtimeController::class)->only(['index', 'create', 'store']);
});