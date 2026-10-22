<?php

// database/seeders/MyBookingsDemoSeeder.php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for the My Bookings screen.
 *
 * Creates: 3 users (customer / staff / admin), genres, 5 movies, 2 screens with seats,
 * 6 showtimes with seat allocations, and 6 bookings covering every status shown in the design.
 *
 * Run:  php artisan db:seed --class=MyBookingsDemoSeeder
 * Login: alex@example.com / password
 */
class MyBookingsDemoSeeder extends Seeder
{
    private const BASE_PRICE_CENTS = 1000;     // standard seat
    private const PREMIUM_SURCHARGE_CENTS = 200;
    private const PREMIUM_ROWS = ['E', 'F', 'G'];

    public function run(): void
    {
        if (DB::table('bookings')->where('booking_reference', 'CB-261015-0482')->exists()) {
            $this->command?->info('Demo bookings already seeded. Run migrate:fresh to start over.');
            return;
        }

        DB::transaction(function () {
            $now = Carbon::now();

            // ---------- Users ----------
            $customerId = $this->upsert('users', ['email' => 'alex@example.com'], [
                'name' => 'Alex Morgan',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'email_verified_at' => $now,
            ]);
            $this->upsert('users', ['email' => 'staff@example.com'], [
                'name' => 'Sam Staff',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'email_verified_at' => $now,
            ]);
            $this->upsert('users', ['email' => 'admin@example.com'], [
                'name' => 'Ada Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => $now,
            ]);

            // ---------- Genres ----------
            $genres = [];
            foreach (['Sci-Fi', 'Thriller', 'Drama', 'Documentary', 'Fantasy'] as $name) {
                $genres[$name] = $this->upsert('genres', ['slug' => strtolower($name)], ['name' => $name]);
            }

            // ---------- Movies ----------
            $movieDefs = [
                'the-last-horizon'  => ['The Last Horizon', 'An astronaut drifts at the edge of a collapsing world.', 128, 'PG-13', ['Sci-Fi', 'Drama']],
                'night-circuit'     => ['Night Circuit', 'A courier races across a neon city before dawn.', 112, 'R', ['Thriller']],
                'a-quiet-october'   => ['A Quiet October', 'A family reunites in a small coastal town.', 104, 'PG', ['Drama']],
                'side-by-side'      => ['Side by Side', 'Two rival chefs are forced to share one kitchen.', 98, 'PG', ['Drama']],
                'the-paper-kingdom' => ['The Paper Kingdom', 'A paper-folding prodigy discovers a hidden realm.', 110, 'PG', ['Fantasy']],
            ];

            $movies = [];
            foreach ($movieDefs as $slug => [$title, $synopsis, $minutes, $rating, $genreNames]) {
                $movies[$slug] = [
                    'id' => $this->upsert('movies', ['slug' => $slug], [
                        'title' => $title,
                        'synopsis' => $synopsis,
                        'duration_minutes' => $minutes,
                        'rating' => $rating,
                        'poster_path' => null,
                        'trailer_url' => null,
                        'release_date' => $now->copy()->subDays(20)->toDateString(),
                        'status' => 'now_showing',
                    ]),
                    'minutes' => $minutes,
                ];
                foreach ($genreNames as $g) {
                    DB::table('genre_movie')->insertOrIgnore([
                        'movie_id' => $movies[$slug]['id'],
                        'genre_id' => $genres[$g],
                    ]);
                }
            }

            // ---------- Screens and seats (rows A-G, 10 seats each) ----------
            $screen1 = $this->upsert('screens', ['name' => 'Screen 1'], ['total_rows' => 7, 'seats_per_row' => 10]);
            $screen2 = $this->upsert('screens', ['name' => 'Screen 2 IMAX'], ['total_rows' => 7, 'seats_per_row' => 10]);

            foreach ([$screen1, $screen2] as $screenId) {
                $rows = [];
                foreach (range('A', 'G') as $row) {
                    foreach (range(1, 10) as $number) {
                        $rows[] = [
                            'screen_id' => $screenId,
                            'row_label' => $row,
                            'seat_number' => $number,
                            'type' => in_array($row, self::PREMIUM_ROWS, true) ? 'premium' : 'standard',
                            'is_disabled' => false,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
                DB::table('seats')->insertOrIgnore($rows);
            }

            // ---------- Bookings (one showtime each) ----------
            // 'hold' => status of the original hold, or null if there is none.
            $bookingDefs = [
                ['ref' => 'CB-261015-0482', 'movie' => 'the-last-horizon',  'screen' => $screen1, 'start' => $now->copy()->addDays(2)->setTime(19, 30),  'seats' => [['G', 7], ['G', 8]], 'status' => 'confirmed',       'hold' => 'converted'],
                ['ref' => 'CB-261016-0510', 'movie' => 'night-circuit',     'screen' => $screen2, 'start' => $now->copy()->addDay()->setTime(18, 0),     'seats' => [['E', 5]],            'status' => 'pending_payment', 'hold' => 'active'],
                ['ref' => 'CB-261010-0314', 'movie' => 'a-quiet-october',   'screen' => $screen1, 'start' => $now->copy()->subDays(9)->setTime(17, 0),   'seats' => [['C', 5]],            'status' => 'refunded',        'hold' => 'converted'],
                ['ref' => 'CB-261009-0291', 'movie' => 'side-by-side',      'screen' => $screen2, 'start' => $now->copy()->subDays(10)->setTime(15, 0),  'seats' => [['B', 2]],            'status' => 'expired',         'hold' => 'expired'],
                ['ref' => 'CB-261008-0275', 'movie' => 'the-paper-kingdom', 'screen' => $screen1, 'start' => $now->copy()->subDays(11)->setTime(16, 0),  'seats' => [['A', 4], ['A', 5]], 'status' => 'failed',          'hold' => 'expired'],
                ['ref' => 'CB-261017-0518', 'movie' => 'night-circuit',     'screen' => $screen2, 'start' => $now->copy()->addDays(3)->setTime(18, 0),   'seats' => [['G', 1]],            'status' => 'cancelled',       'hold' => 'converted'],
            ];

            foreach ($bookingDefs as $i => $def) {
                $movie = $movies[$def['movie']];
                $start = $def['start'];

                // Showtime
                $showtimeId = DB::table('showtimes')->insertGetId([
                    'movie_id' => $movie['id'],
                    'screen_id' => $def['screen'],
                    'starts_at' => $start,
                    'ends_at' => $start->copy()->addMinutes($movie['minutes']),
                    'base_price_cents' => self::BASE_PRICE_CENTS,
                    'status' => 'scheduled',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Seat allocation rows for the whole screen (all start as available)
                $allocations = DB::table('seats')->where('screen_id', $def['screen'])->get()->map(fn ($seat) => [
                    'showtime_id' => $showtimeId,
                    'seat_id' => $seat->id,
                    'status' => 'available',
                    'hold_id' => null,
                    'booking_id' => null,
                    'held_until' => null,
                    'price_cents' => self::BASE_PRICE_CENTS
                        + ($seat->type === 'premium' ? self::PREMIUM_SURCHARGE_CENTS : 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();
                DB::table('showtime_seats')->insert($allocations);

                // Hold
                $holdId = null;
                if ($def['hold']) {
                    $holdId = DB::table('holds')->insertGetId([
                        'user_id' => $customerId,
                        'showtime_id' => $showtimeId,
                        'status' => $def['hold'],
                        'expires_at' => $def['hold'] === 'active'
                            ? $now->copy()->addMinutes(8)
                            : $start->copy()->subHours(3),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                // Seats and total
                $seatRows = [];
                $total = 0;
                foreach ($def['seats'] as [$row, $number]) {
                    $seat = DB::table('seats')
                        ->where(['screen_id' => $def['screen'], 'row_label' => $row, 'seat_number' => $number])
                        ->first();
                    $price = self::BASE_PRICE_CENTS
                        + ($seat->type === 'premium' ? self::PREMIUM_SURCHARGE_CENTS : 0);
                    $seatRows[] = ['seat_id' => $seat->id, 'price_cents' => $price];
                    $total += $price;
                }

                // Booking
                $createdAt = $now->copy()->subDays(max(1, 12 - $i * 2));
                $bookingId = DB::table('bookings')->insertGetId([
                    'booking_reference' => $def['ref'],
                    'user_id' => $customerId,
                    'showtime_id' => $showtimeId,
                    'hold_id' => $holdId,
                    'status' => $def['status'],
                    'total_amount_cents' => $total,
                    'currency' => 'USD',
                    'confirmed_at' => in_array($def['status'], ['confirmed', 'refunded', 'cancelled'], true) ? $createdAt : null,
                    'cancelled_at' => in_array($def['status'], ['cancelled', 'refunded'], true) ? $now->copy()->subHours(5) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Booking seats
                $bookingSeatIds = [];
                foreach ($seatRows as $row) {
                    $bookingSeatIds[] = DB::table('booking_seats')->insertGetId([
                        'booking_id' => $bookingId,
                        'seat_id' => $row['seat_id'],
                        'price_cents' => $row['price_cents'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                // Reflect the booking on the seat allocation
                $seatIds = array_column($seatRows, 'seat_id');
                if ($def['status'] === 'confirmed') {
                    DB::table('showtime_seats')
                        ->where('showtime_id', $showtimeId)->whereIn('seat_id', $seatIds)
                        ->update(['status' => 'sold', 'booking_id' => $bookingId]);
                } elseif ($def['status'] === 'pending_payment') {
                    DB::table('showtime_seats')
                        ->where('showtime_id', $showtimeId)->whereIn('seat_id', $seatIds)
                        ->update(['status' => 'held', 'hold_id' => $holdId, 'held_until' => $now->copy()->addMinutes(8)]);
                }

                // Payments (no payment row for an expired booking)
                $paymentId = null;
                if ($def['status'] !== 'expired') {
                    $paymentStatus = match ($def['status']) {
                        'pending_payment' => 'pending',
                        'failed' => 'failed',
                        'refunded' => 'refunded',
                        default => 'paid',
                    };
                    $isPaid = in_array($paymentStatus, ['paid', 'refunded'], true);

                    $paymentId = DB::table('payments')->insertGetId([
                        'booking_id' => $bookingId,
                        'provider' => 'stripe',
                        'provider_session_id' => 'cs_test_demo_' . ($i + 1),
                        'provider_payment_id' => $isPaid ? 'pi_test_demo_' . ($i + 1) : null,
                        'amount_cents' => $total,
                        'currency' => 'USD',
                        'status' => $paymentStatus,
                        'paid_at' => $isPaid ? $createdAt : null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                // Refunds
                if ($def['status'] === 'refunded') {
                    DB::table('refunds')->insert([
                        'payment_id' => $paymentId,
                        'amount_cents' => $total,
                        'provider_refund_id' => 're_test_demo_1',
                        'status' => 'succeeded',
                        'reason' => 'Customer cancellation',
                        'requested_at' => $now->copy()->subDays(10),
                        'completed_at' => $now->copy()->subDays(10),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } elseif ($def['status'] === 'cancelled') {
                    DB::table('refunds')->insert([
                        'payment_id' => $paymentId,
                        'amount_cents' => $total,
                        'provider_refund_id' => null,
                        'status' => 'pending',
                        'reason' => 'Customer cancellation',
                        'requested_at' => $now->copy()->subHours(5),
                        'completed_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                // Tickets: valid for confirmed bookings, void for refunded/cancelled ones
                if (in_array($def['status'], ['confirmed', 'refunded', 'cancelled'], true)) {
                    foreach ($bookingSeatIds as $bookingSeatId) {
                        DB::table('tickets')->insert([
                            'booking_id' => $bookingId,
                            'booking_seat_id' => $bookingSeatId,
                            'token' => bin2hex(random_bytes(20)),   // 40 lowercase hex chars
                            'status' => $def['status'] === 'confirmed' ? 'valid' : 'void',
                            'used_at' => null,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);
                    }
                }
            }
        });
    }

    /** Insert or update a row matched by $match; returns its id. */
    private function upsert(string $table, array $match, array $values): int
    {
        $now = Carbon::now();
        DB::table($table)->updateOrInsert($match, $values + ['created_at' => $now, 'updated_at' => $now]);

        return (int) DB::table($table)->where($match)->value('id');
    }
}