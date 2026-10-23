<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\RefundStatus;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
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

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function hold(): BelongsTo
    {
        return $this->belongsTo(Hold::class);
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function showtimeSeats(): HasMany
    {
        return $this->hasMany(ShowtimeSeat::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Admin-facing state, derived from booking + payment + refund status.
     * Eager load "payments.refunds" before calling this (avoids N+1).
     *
     * @return array{label: string, tone: string}
     */
    /**
     * Admin-facing state label + tone for the booking history table.
     * Eager load "payments.refunds" before calling this (avoids N+1).
     *
     * @return array{label: string, tone: string}
     */
    public function adminState(): array
    {
        return match ($this->status) {
            BookingStatus::Confirmed => ['label' => 'Confirmed', 'tone' => 'info'],
            BookingStatus::PendingPayment => ['label' => 'Pending payment', 'tone' => 'warning'],
            BookingStatus::Refunded => ['label' => 'Refunded', 'tone' => 'success'],
            BookingStatus::Expired => ['label' => 'Expired', 'tone' => 'muted'],
            BookingStatus::Failed => ['label' => 'Failed', 'tone' => 'danger'],
            BookingStatus::Cancelled => $this->cancelledState(),
        };
    }

    /** A cancelled booking is "refund pending" until the refund succeeds. */
    private function cancelledState(): array
    {
        $refunds = $this->payments->flatMap(fn($payment) => $payment->refunds);

        if ($refunds->contains(fn($refund) => $refund->status === RefundStatus::Pending)) {
            return ['label' => 'Cancelled · refund pending', 'tone' => 'muted'];
        }

        if ($refunds->contains(fn($refund) => $refund->status === RefundStatus::Succeeded)) {
            return ['label' => 'Refunded', 'tone' => 'success'];
        }

        return ['label' => 'Cancelled', 'tone' => 'muted'];
    }
}
