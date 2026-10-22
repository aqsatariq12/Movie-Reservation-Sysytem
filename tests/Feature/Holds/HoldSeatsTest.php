<?php

use App\Actions\Holds\ExpireHoldsAction;
use App\Actions\Holds\HoldSeatsAction;
use App\Models\Hold;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

require_once __DIR__ . '/helpers.php';

uses(RefreshDatabase::class);

// Required test 1: a seat cannot be held twice for the same showtime
it('does not let a seat be held twice for the same showtime', function () {
    [$showtime, $screen] = makeShowtime();
    $seatIds = seatIds($screen, 2);
    $action = app(HoldSeatsAction::class);

    $action->execute(User::factory()->create(), $showtime, $seatIds);

    expect(fn() => $action->execute(User::factory()->create(), $showtime, $seatIds))
        ->toThrow(ValidationException::class);

    expect(Hold::count())->toBe(1);
});

// All or nothing
it('holds all requested seats or none of them', function () {
    [$showtime, $screen] = makeShowtime();
    [$a, $b, $c] = seatIds($screen, 3);
    $action = app(HoldSeatsAction::class);

    $action->execute(User::factory()->create(), $showtime, [$a, $b]);

    // $b is taken and $c is free: the whole request must fail and $c must stay available
    expect(fn() => $action->execute(User::factory()->create(), $showtime, [$b, $c]))
        ->toThrow(ValidationException::class);

    expect(ShowtimeSeat::where('showtime_id', $showtime->id)->where('seat_id', $c)->value('status'))
        ->toBe('available');
});

// Required test 2: an expired hold releases the seat
it('releases the seats when a hold expires', function () {
    [$showtime, $screen] = makeShowtime();
    $seatIds = seatIds($screen, 2);

    $booking = app(HoldSeatsAction::class)->execute(User::factory()->create(), $showtime, $seatIds);

    $this->travel(11)->minutes();

    expect(app(ExpireHoldsAction::class)->execute())->toBe(1);

    expect(ShowtimeSeat::where('showtime_id', $showtime->id)->whereIn('seat_id', $seatIds)->pluck('status')->unique()->all())
        ->toBe(['available']);
    expect($booking->fresh()->status)->toBe('expired');
});