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
        $user = User::query()->where('id', 1)->first();

        if (! $user) {
            throw new RuntimeException(
                'User ID 1 nahi mila. Pehle apne user ki ID check karein.'
            );
        }

        // Create or update the test movie.
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

        // Create or update the test screen.
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

        // Create or reuse the test showtime.
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

        // Create four uniquely identified test bookings.
        for ($i = 1; $i <= 4; $i++) {
            $reference = sprintf(
                'STRIPE-BATCH-%s-%02d',
                now()->format('YmdHis'),
                $i
            );

            $holdId = DB::table('holds')->insertGetId([
                'user_id' => $user->id,
                'showtime_id' => $showtimeId,
                'status' => 'active',
                'expires_at' => now()->addHours(2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $bookingId = DB::table('bookings')->insertGetId([
                'booking_reference' => $reference,
                'user_id' => $user->id,
                'showtime_id' => $showtimeId,
                'hold_id' => $holdId,
                'status' => 'pending_payment',
                'total_amount_cents' => 1000,
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('payments')->insert([
                'booking_id' => $bookingId,
                'provider' => 'stripe',
                'provider_session_id' => null,
                'provider_payment_id' => null,
                'amount_cents' => 1000,
                'currency' => 'USD',
                'status' => 'pending',
                'paid_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info(
                "Created booking ID {$bookingId}: {$reference}"
            );
        }

        $this->command->info('Four Stripe test bookings created.');
        $this->command->info('Each booking amount: $10.00 USD');
        $this->command->info('Existing bookings and payments were not updated.');
    }
}
