<?php

use App\Actions\Holds\ExpireHoldsAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Display an inspiring quote.
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire holds and their pending-payment bookings every minute.
Schedule::call(function () {
    app(ExpireHoldsAction::class)->execute();
})
    ->name('expire-holds')
    ->everyMinute()
    ->withoutOverlapping();

// Check pending Stripe payments and reconcile missed webhook events.
Schedule::command('stripe:reconcile')
    ->everyMinute()
    ->withoutOverlapping();
