<?php

namespace App\Models;

use App\Enums\SeatType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'screen_id',
        'row_label',
        'seat_number',
        'type',
        'is_disabled',
    ];

    protected function casts(): array
    {
        return [
            'type' => SeatType::class,
            'is_disabled' => 'boolean',
        ];
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function showtimeSeats(): HasMany
    {
        return $this->hasMany(ShowtimeSeat::class);
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    /** Human label such as "B4". */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => $this->row_label.$this->seat_number);
    }
}
