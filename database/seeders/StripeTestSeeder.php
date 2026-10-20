<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StripeTestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        if (! $user) {
            throw new RuntimeException(
                'Users table mein user nahi hai. Pehle register karein.'
            );
        }

        // 1. Create test movie
        DB::table('movies')->updateOrInsert(
            ['slug' => 'stripe-test-movie'],
            [
                'title' => 'Stripe Test Movie',
                'synopsis' => 'Dummy movie for Stripe testing.',
                'duration_minutes' => 120,
                'rating' => 'PG-13',
                'status' => 'now_showing',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $movieId = DB::table('movies')
            ->where('slug', 'stripe-test-movie')
            ->value('id');

        // 2. Create test screen
        DB::table('screens')->updateOrInsert(
            ['name' => 'Stripe Test Screen'],
            [
                'total_rows' => 5,
                'seats_per_row' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $screenId = DB::table('screens')
            ->where('name', 'Stripe Test Screen')
            ->value('id');

        // 3. Create test showtime
        $startsAt = '2027-01-15 19:00:00';

        DB::table('showtimes')->updateOrInsert(
            [
                'movie_id' => $movieId,
                'screen_id' => $screenId,
                'starts_at' => $startsAt,
            ],
            [
                'ends_at' => '2027-01-15 21:00:00',
                'base_price_cents' => 1000,
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $showtimeId = DB::table('showtimes')
            ->where('movie_id', $movieId)
            ->where('screen_id', $screenId)
            ->where('starts_at', $startsAt)
            ->value('id');

        // 4. Create test hold
        $holdId = DB::table('holds')->insertGetId([
            'user_id' => $user->id,
            'showtime_id' => $showtimeId,
            'status' => 'active',
            'expires_at' => now()->addHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Create test booking only once
        $booking = DB::table('bookings')
            ->where('booking_reference', 'STRIPE-TEST-0001')
            ->first();

        if (! $booking) {
            $bookingId = DB::table('bookings')->insertGetId([
                'booking_reference' => 'STRIPE-TEST-0001',
                'user_id' => $user->id,
                'showtime_id' => $showtimeId,
                'hold_id' => $holdId,
                'status' => 'pending_payment',
                'total_amount_cents' => 1000,
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $bookingId = $booking->id;
        }

        $this->command->info('Stripe test data created.');
        $this->command->info("User ID: {$user->id}");
        $this->command->info("Booking ID: {$bookingId}");
        $this->command->info('Booking Reference: STRIPE-TEST-0001');
        $this->command->info('Amount: $10.00 USD');
    }
}
