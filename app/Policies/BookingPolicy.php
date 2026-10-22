<?php

// app/Policies/BookingPolicy.php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * A customer may only view their own bookings.
     * (Required test #6: a customer cannot view another customer's booking.)
     */
    public function view(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }
}