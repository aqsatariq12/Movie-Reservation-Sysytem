<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Showtime extends Model
{
    public const TURNAROUND_MINUTES = 15;

    protected $fillable = [
        'movie_id',
        'screen_id',
        'starts_at',
        'ends_at',
        'base_price_cents',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'base_price_cents' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function showtimeSeats(): HasMany
    {
        return $this->hasMany(ShowtimeSeat::class);
    }

    public function holds(): HasMany
    {
        return $this->hasMany(Hold::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** 1000 => "$10.00" (integer maths only, never floats for money) */
    public static function money(int $cents): string
    {
        return '$' . intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Reads an integer rule from the settings table. TODO: use the owner's Setting model once merged. */
    public static function setting(string $key, int $default): int
    {
        return (int) (DB::table('settings')->where('key', $key)->value('value') ?? $default);
    }

    public static function premiumSurchargeCents(): int
    {
        return self::setting('premium_surcharge_cents', 200);
    }
}