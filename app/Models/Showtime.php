<?php

namespace App\Models;

use App\Enums\ShowtimeStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Showtime extends Model
{
    use HasFactory;

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
            'status' => ShowtimeStatus::class,
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

    public function scopeScheduled(Builder $query): void
    {
        $query->where('status', ShowtimeStatus::Scheduled->value);
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now());
    }

    /** Showtimes that start on the given calendar day. */
    public function scopeOnDate(Builder $query, CarbonInterface $date): void
    {
        $query->whereBetween('starts_at', [
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
        ]);
    }
    public static function money(int $cents): string
    {
        return '$' . number_format($cents / 100, 2);
    }
}
