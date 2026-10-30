<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'provider',
        'provider_session_id',
        'provider_payment_id',
        'amount_cents',
        'currency',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'amount_cents' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

        /** A pending payment older than this is flagged as "stuck" in admin. */
    public const STUCK_AFTER_MINUTES = 15;

    public function isStuck(): bool
    {
        return $this->status === PaymentStatus::Pending
            && $this->created_at !== null
            && $this->created_at->lt(now()->subMinutes(self::STUCK_AFTER_MINUTES));
    }

    /**
     * Admin-facing label + tone for the payments table.
     *
     * @return array{label: string, tone: string}
     */
    public function adminState(): array
    {
        return match ($this->status) {
            PaymentStatus::Paid => ['label' => 'Succeeded', 'tone' => 'brand'],
            PaymentStatus::Pending => [
                'label' => $this->isStuck() ? 'Processing · stuck' : 'Processing',
                'tone' => 'brand',
            ],
            PaymentStatus::Failed => ['label' => 'Failed', 'tone' => 'muted'],
            PaymentStatus::Refunded => ['label' => 'Refunded', 'tone' => 'muted'],
            PaymentStatus::Expired => ['label' => 'Expired', 'tone' => 'muted'],
        };
    }
}
