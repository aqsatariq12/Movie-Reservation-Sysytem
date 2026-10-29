<?php

namespace App\Services;
use App\Models\Booking;
use Stripe\StripeClient;

class StripePaymentService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe =new StripeClient(
            config('stripe.secret')
        );
    }
    public function createCheckoutSession(Booking $booking): \Stripe\Checkout\Session
    {
        $session = $this->stripe->checkout->sessions->create([
        'mode'=> 'payment',
      

        'line_items' => [
            [
                'price_data' => [
                    'currency' => strtolower($booking->currency),
                    'product_data'=> [
                        'name' => 'Movie Reservation - '
                        . $booking->booking_reference,
                    ],
                    'unit_amount' => $booking->total_amount_cents,
                ],
                 'quantity' => 1,
            ],
        ],
       'metadata' => [
    'booking_id' => (string) $booking->id,
    'booking_reference' => $booking->booking_reference,
],

'payment_intent_data' => [
    'metadata' => [
        'booking_id' => (string) $booking->id,
        'booking_reference' => $booking->booking_reference,
    ],
],

        'success_url' => route('payment.success')
         . '?session_id={CHECKOUT_SESSION_ID}',

       'cancel_url' => route('payment.cancel', [
    'booking_id' => $booking->id,
]),
        ]);

         return $session;
    }
        public function retrieveCheckoutSession(string $sessionId): \Stripe\Checkout\Session
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId);
    }
public function expireCheckoutSession(string $sessionId): \Stripe\Checkout\Session
{
    return $this->stripe->checkout->sessions->expire($sessionId);
}

public function createRefund(
    string $paymentIntentId,
    ?int $amountCents = null,
    ?string $idempotencyKey = null
): \Stripe\Refund {
    $params = [
        'payment_intent' => $paymentIntentId,
    ];

    if ($amountCents !== null) {
        $params['amount'] = $amountCents;
    }

    $options = [];

    if ($idempotencyKey !== null) {
        $options['idempotency_key'] = $idempotencyKey;
    }

    return $this->stripe->refunds->create($params, $options);
}


public function retrievePaymentIntent(string $paymentIntentId): \Stripe\PaymentIntent
{
    return $this->stripe->paymentIntents->retrieve($paymentIntentId);
}

}
