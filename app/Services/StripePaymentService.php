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
        'metadata' =>[
         'booking_id' => (string) $booking->id,
         'booking_reference' => $booking->booking_reference,
        ],

        'success_url' => route('payment.success')
         . '?session_id={CHECKOUT_SESSION_ID}',

         'cancel_url' => route('payment.cancel'),
        ]);

         return $session;
    }
        public function retrieveCheckoutSession(string $sessionId): \Stripe\Checkout\Session
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId);
    }

}
