<?php

use App\Http\Controllers\Admin\ScreenController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

// TODO: add the owner's role middleware, e.g. ['auth', 'verified', 'role:admin'], once merged
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('screens', ScreenController::class);
    Route::patch('screens/{screen}/seats/{seat}/toggle', [ScreenController::class, 'toggleSeat'])
        ->name('screens.seats.toggle');
});