<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShowtimeSeat extends Model
{
    protected $fillable = [
        'showtime_id',
        'seat_id',
        'status',
        'hold_id',
        'booking_id',
        'held_until',
        'price_cents',
    ];

    protected function casts(): array
    {
        return [
            'held_until' => 'datetime',
            'price_cents' => 'integer',
        ];
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function hold(): BelongsTo
    {
        return $this->belongsTo(Hold::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}