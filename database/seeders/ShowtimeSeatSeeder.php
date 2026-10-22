<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShowtimeSeatSeeder extends Seeder
{
    private const PREMIUM_SURCHARGE_CENTS = 200;

    public function run(): void
    {
        DB::transaction(function () {
            $now = now();

            // Find only seat allocations that do not exist yet.
            $missingSeats = DB::table('showtimes')
                ->join(
                    'seats',
                    'seats.screen_id',
                    '=',
                    'showtimes.screen_id'
                )
                ->leftJoin('showtime_seats', function ($join) {
                    $join->on(
                        'showtime_seats.showtime_id',
                        '=',
                        'showtimes.id'
                    )->on(
                        'showtime_seats.seat_id',
                        '=',
                        'seats.id'
                    );
                })
                ->whereNull('showtime_seats.id')
                ->select(
                    'showtimes.id as showtime_id',
                    'showtimes.base_price_cents',
                    'seats.id as seat_id',
                    'seats.type'
                )
                ->get();

            $inserted = 0;

            foreach ($missingSeats->chunk(500) as $chunk) {
                $rows = $chunk->map(fn ($seat) => [
                    'showtime_id' => $seat->showtime_id,
                    'seat_id' => $seat->seat_id,
                    'status' => 'available',
                    'hold_id' => null,
                    'booking_id' => null,
                    'held_until' => null,
                    'price_cents' => $seat->base_price_cents
                        + ($seat->type === 'premium'
                            ? self::PREMIUM_SURCHARGE_CENTS
                            : 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('showtime_seats')->insertOrIgnore($rows);
                $inserted += count($rows);
            }

            $this->command?->info(
                "Processed {$inserted} missing seat allocations."
            );
        });
    }
}
