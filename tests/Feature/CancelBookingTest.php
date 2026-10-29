
<?php

use App\Actions\Bookings\CancelBookingAction;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ShowtimeSeatStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function createCancellationTestBooking(
    User $user,
    array $overrides = []
): Booking {
    $now = now();

    $movieId = DB::table('movies')->insertGetId([
        'title' => 'Test Movie',
        'slug' => 'test-movie-' . uniqid(),
        'synopsis' => 'A movie for testing.',
        'duration_minutes' => 120,
        'status' => 'now_showing',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $screenId = DB::table('screens')->insertGetId([
        'name' => 'Test Screen ' . uniqid(),
        'total_rows' => 1,
        'seats_per_row' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $showtimeId = DB::table('showtimes')->insertGetId([
        'movie_id' => $movieId,
        'screen_id' => $screenId,
        'starts_at' => $overrides['starts_at'] ?? $now->copy()->addHours(5),
        'ends_at' => $now->copy()->addHours(7),
        'base_price_cents' => 2000,
        'status' => 'scheduled',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $seatId = DB::table('seats')->insertGetId([
        'screen_id' => $screenId,
        'row_label' => 'A',
        'seat_number' => 1,
        'type' => 'standard',
        'is_disabled' => false,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bookingId = DB::table('bookings')->insertGetId([
        'booking_reference' => strtoupper(uniqid('TEST')),
        'user_id' => $user->id,
        'showtime_id' => $showtimeId,
        'status' => BookingStatus::Confirmed->value,
        'total_amount_cents' => 2000,
        'currency' => 'USD',
        'confirmed_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bookingSeatId = DB::table('booking_seats')->insertGetId([
        'booking_id' => $bookingId,
        'seat_id' => $seatId,
        'price_cents' => 2000,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('showtime_seats')->insert([
        'showtime_id' => $showtimeId,
        'seat_id' => $seatId,
        'status' => ShowtimeSeatStatus::Sold->value,
        'booking_id' => $bookingId,
        'price_cents' => 2000,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('payments')->insert([
        'booking_id' => $bookingId,
        'provider' => 'stripe',
        'provider_session_id' => 'session_' . uniqid(),
        'provider_payment_id' => 'pi_test_' . uniqid(),
        'amount_cents' => 2000,
        'currency' => 'USD',
        'status' => PaymentStatus::Paid->value,
        'paid_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('tickets')->insert([
        'booking_id' => $bookingId,
        'booking_seat_id' => $bookingSeatId,
        'token' => bin2hex(random_bytes(20)),
        'status' => $overrides['ticket_status'] ?? TicketStatus::Valid->value,
        'used_at' => $overrides['used_at'] ?? null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return Booking::findOrFail($bookingId);
}

test('customer can cancel a confirmed paid booking and request a full refund', function () {
    $user = User::factory()->create();

    $booking = createCancellationTestBooking($user);

    $refund = app(CancelBookingAction::class)->execute(
        $booking,
        (int) $user->id
    );

    expect($refund)->toBeInstanceOf(Refund::class)
        ->and($refund->amount_cents)->toBe(2000)
        ->and($refund->status)->toBe(RefundStatus::Pending)
        ->and($refund->reason)->toBe('customer_booking_cancelled');

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => BookingStatus::Cancelled->value,
    ]);

    $this->assertDatabaseHas('tickets', [
        'booking_id' => $booking->id,
        'status' => TicketStatus::Void->value,
    ]);

    $this->assertDatabaseHas('showtime_seats', [
        'showtime_id' => $booking->showtime_id,
        'status' => ShowtimeSeatStatus::Available->value,
        'booking_id' => null,
        'hold_id' => null,
    ]);
});

test('customer cannot cancel within the two-hour cancellation window', function () {
    $user = User::factory()->create();

    $booking = createCancellationTestBooking($user, [
        'starts_at' => now()->addHour(),
    ]);

    expect(fn () => app(CancelBookingAction::class)->execute(
        $booking,
        (int) $user->id
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => BookingStatus::Confirmed->value,
    ]);

    $this->assertDatabaseCount('refunds', 0);
});

test('customer cannot cancel a booking with a used ticket', function () {
    $user = User::factory()->create();

    $booking = createCancellationTestBooking($user, [
        'ticket_status' => TicketStatus::Used->value,
        'used_at' => now(),
    ]);

    expect(fn () => app(CancelBookingAction::class)->execute(
        $booking,
        (int) $user->id
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => BookingStatus::Confirmed->value,
    ]);

    $this->assertDatabaseCount('refunds', 0);
});

test('another customer cannot cancel someone elses booking', function () {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();

    $booking = createCancellationTestBooking($owner);

    expect(fn () => app(CancelBookingAction::class)->execute(
        $booking,
        (int) $anotherUser->id
    ))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => BookingStatus::Confirmed->value,
    ]);

    $this->assertDatabaseCount('refunds', 0);
});

test('customer cancellation flow displays the confirmation and pending refund pages', function () {
    $user = User::factory()->create();

    $booking = createCancellationTestBooking($user);

    // The confirmation page should render for the booking owner.
    $this->actingAs($user)
        ->get(route('bookings.cancel.confirm', $booking))
        ->assertOk()
        ->assertSee('Cancel your movie night?')
        ->assertSee('Yes, cancel');

    // Submit the cancellation request. This creates a pending
    // refund record but does not call Stripe.
    $this->actingAs($user)
        ->post(route('bookings.cancel', $booking))
        ->assertRedirect(route('bookings.cancelled', $booking));

    // The result page should show that the refund is pending.
    $this->actingAs($user)
        ->get(route('bookings.cancelled', $booking))
        ->assertOk()
        ->assertSee('Booking cancelled. Refund requested.')
        ->assertSee('Refund pending');
});
