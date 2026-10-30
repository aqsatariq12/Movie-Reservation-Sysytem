
<?php

use App\Actions\Refunds\RetryRefundAction;
use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Services\StripePaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Refund as StripeRefund;

function createRetryRefundTestRecord(): Refund
{
    $now = now();

    $user = \App\Models\User::factory()->create();

    $movieId = DB::table('movies')->insertGetId([
        'title' => 'Retry Test Movie',
        'slug' => 'retry-test-' . uniqid(),
        'synopsis' => 'A movie for refund retry testing.',
        'duration_minutes' => 100,
        'status' => 'now_showing',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $screenId = DB::table('screens')->insertGetId([
        'name' => 'Retry Test Screen ' . uniqid(),
        'total_rows' => 1,
        'seats_per_row' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $showtimeId = DB::table('showtimes')->insertGetId([
        'movie_id' => $movieId,
        'screen_id' => $screenId,
        'starts_at' => $now->copy()->addDays(2),
        'ends_at' => $now->copy()->addDays(2)->addHours(2),
        'base_price_cents' => 2000,
        'status' => 'scheduled',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $bookingId = DB::table('bookings')->insertGetId([
        'booking_reference' => strtoupper(uniqid('RT')),
        'user_id' => $user->id,
        'showtime_id' => $showtimeId,
        'status' => 'cancelled',
        'total_amount_cents' => 2000,
        'currency' => 'USD',
        'confirmed_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $paymentId = DB::table('payments')->insertGetId([
        'booking_id' => $bookingId,
        'provider' => 'stripe',
        'provider_session_id' => 'cs_test_' . uniqid(),
        'provider_payment_id' => 'pi_test_' . uniqid(),
        'amount_cents' => 2000,
        'currency' => 'USD',
        'status' => 'paid',
        'paid_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $refundId = DB::table('refunds')->insertGetId([
        'payment_id' => $paymentId,
        'amount_cents' => 1000,
        'provider_refund_id' => 're_test_' . uniqid(),
        'status' => 'failed',
        'reason' => 'admin_booking_cancelled',
        'requested_at' => $now,
        'completed_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return Refund::findOrFail($refundId);
}

it('creates a new refund attempt without changing the original refund', function () {
    $original = createRetryRefundTestRecord();

    $stripeRefund = StripeRefund::constructFrom([
        'id' => 're_test_retry_' . uniqid(),
        'object' => 'refund',
        'amount' => 1000,
        'status' => 'succeeded',
        'payment_intent' => $original->payment->provider_payment_id,
    ]);

    $stripe = Mockery::mock(StripePaymentService::class);

    $emptyRefundCollection = new \Stripe\Collection();
    $emptyRefundCollection->data = [];

    $stripe->shouldReceive('retrieveRefundsForPaymentIntent')
        ->once()
        ->with($original->payment->provider_payment_id)
        ->andReturn($emptyRefundCollection);

    $stripe->shouldReceive('createRefund')
        ->once()
        ->with(
            $original->payment->provider_payment_id,
            1000,
            Mockery::type('string'),
            Mockery::on(function ($metadata) {
                return is_array($metadata)
                    && isset($metadata['local_refund_id'])
                    && is_string($metadata['local_refund_id']);
            })
        )
        ->andReturn($stripeRefund);

    app()->instance(StripePaymentService::class, $stripe);

    $newRefund = app(RetryRefundAction::class)->execute($original);

    expect($newRefund->id)->not->toBe($original->id)
        ->and($newRefund->retry_of_refund_id)->toBe($original->id)
        ->and($newRefund->status)->toBe(RefundStatus::Succeeded)
        ->and($newRefund->amount_cents)->toBe(1000);

    expect($original->fresh()->status)->toBe(RefundStatus::Failed)
        ->and($original->fresh()->provider_refund_id)->not->toBe($stripeRefund->id);

    expect(
        DB::table('refunds')
            ->where('payment_id', $original->payment_id)
            ->count()
    )->toBe(2);
});

it('rejects a refund with an invalid Stripe refund reference without calling Stripe', function () {
    $original = createRetryRefundTestRecord();

    DB::table('refunds')
        ->where('id', $original->id)
        ->update([
            'provider_refund_id' => 'UI-TEST-ONLY-REFUND',
        ]);

    $stripe = Mockery::mock(StripePaymentService::class);

    $stripe->shouldNotReceive('retrieveRefundsForPaymentIntent');
    $stripe->shouldNotReceive('createRefund');

    app()->instance(StripePaymentService::class, $stripe);

    expect(
        fn() => app(RetryRefundAction::class)->execute($original->fresh())
    )->toThrow(ValidationException::class);
});

it('processes an existing pending refund without creating a duplicate', function () {
    $refund = createRetryRefundTestRecord();

    DB::table('refunds')
        ->where('id', $refund->id)
        ->update([
            'status' => 'pending',
            'provider_refund_id' => null,
            'completed_at' => null,
        ]);

    $refund->refresh();

    $emptyRefundCollection = new \Stripe\Collection();
    $emptyRefundCollection->data = [];

    $stripeRefund = StripeRefund::constructFrom([
        'id' => 're_test_pending_' . uniqid(),
        'object' => 'refund',
        'amount' => 1000,
        'status' => 'pending',
        'payment_intent' => $refund->payment->provider_payment_id,
    ]);

    $stripeRefund->metadata = (object) [];

    $stripe = Mockery::mock(StripePaymentService::class);

    $stripe->shouldReceive('retrieveRefundsForPaymentIntent')
        ->once()
        ->with($refund->payment->provider_payment_id)
        ->andReturn($emptyRefundCollection);

    $stripe->shouldReceive('createRefund')
        ->once()
        ->with(
            $refund->payment->provider_payment_id,
            1000,
            'local-refund-' . $refund->id,
            ['local_refund_id' => (string) $refund->id]
        )
        ->andReturn($stripeRefund);

    app()->instance(StripePaymentService::class, $stripe);

    $result = app(RetryRefundAction::class)->execute($refund);

    expect($result->id)->toBe($refund->id)
        ->and($result->status)->toBe(RefundStatus::Pending)
        ->and($result->provider_refund_id)->toBe($stripeRefund->id);

    expect(
        DB::table('refunds')
            ->where('payment_id', $refund->payment_id)
            ->count()
    )->toBe(1);
});
