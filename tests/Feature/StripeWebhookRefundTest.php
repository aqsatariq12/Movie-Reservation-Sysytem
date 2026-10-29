
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createRefundWebhookTestPayment(): array
{
    $now = now();

    $user = User::factory()->create();

    $movieId = DB::table('movies')->insertGetId([
        'title' => 'Refund Test Movie',
        'slug' => 'refund-test-' . uniqid(),
        'synopsis' => 'A movie for refund webhook testing.',
        'duration_minutes' => 100,
        'status' => 'now_showing',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $screenId = DB::table('screens')->insertGetId([
        'name' => 'Refund Test Screen ' . uniqid(),
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
        'booking_reference' => strtoupper(uniqid('RF')),
        'user_id' => $user->id,
        'showtime_id' => $showtimeId,
        'status' => 'cancelled',
        'total_amount_cents' => 2000,
        'currency' => 'USD',
        'confirmed_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $paymentIntent = 'pi_test_' . uniqid();

    $paymentId = DB::table('payments')->insertGetId([
        'booking_id' => $bookingId,
        'provider' => 'stripe',
        'provider_session_id' => 'cs_test_' . uniqid(),
        'provider_payment_id' => $paymentIntent,
        'amount_cents' => 2000,
        'currency' => 'USD',
        'status' => 'paid',
        'paid_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return [
        'payment_id' => $paymentId,
        'booking_id' => $bookingId,
        'payment_intent' => $paymentIntent,
    ];
}

function sendSignedRefundWebhook(
    string $eventId,
    string $refundId,
    string $paymentIntent
): \Illuminate\Testing\TestResponse {
    $secret = 'whsec_test_secret';

    config([
        'stripe.webhook_secret' => $secret,
    ]);

    $payload = json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => 'refund.updated',
        'data' => [
            'object' => [
                'id' => $refundId,
                'object' => 'refund',
                'payment_intent' => $paymentIntent,
                'amount' => 2000,
                'currency' => 'usd',
                'status' => 'succeeded',
                'created' => time(),
                'reason' => 'requested_by_customer',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $timestamp = time();

    $signature = hash_hmac(
        'sha256',
        $timestamp . '.' . $payload,
        $secret
    );

    return test()->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload
    );
}

it('rejects an ambiguous match between multiple pending refunds', function () {
    $payment = createRefundWebhookTestPayment();
    $now = now();

    foreach ([1, 2] as $number) {
        DB::table('refunds')->insert([
            'payment_id' => $payment['payment_id'],
            'amount_cents' => 2000,
            'provider_refund_id' => null,
            'status' => 'pending',
            'reason' => 'customer_booking_cancelled',
            'requested_at' => $now,
            'completed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $response = sendSignedRefundWebhook(
        'evt_refund_ambiguous_' . uniqid(),
        're_test_ambiguous_' . uniqid(),
        $payment['payment_intent']
    );

    $response->assertStatus(500);

    expect(
        DB::table('refunds')
            ->where('payment_id', $payment['payment_id'])
            ->whereNull('provider_refund_id')
            ->where('status', 'pending')
            ->count()
    )->toBe(2);

    expect(
        DB::table('webhook_events')->count()
    )->toBe(0);
});
