<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable =[
        'booking_reference',
        'user_id',
        'showtime_id',
        'hold_id',
        'status',
        'total_amount_cents',
        'currency',
        'confirmed_at',
        'cancelled_at',
    ]; 
    
    protected function casts(): array{
        return [
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
           return $this->belongsTo(User::class);
    }
     public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function hold(): BelongsTo
{
    return $this->belongsTo(Hold::class);
}
public function showtime(): BelongsTo
{
    return $this->belongsTo(Showtime::class);
}
}
