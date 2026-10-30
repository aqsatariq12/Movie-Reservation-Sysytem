<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'retry_of_refund_id',
        'amount_cents',
        'provider_refund_id',
        'status',
        'reason',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'amount_cents' => 'integer',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function originalRefund(): BelongsTo
    {
        return $this->belongsTo(
            Refund::class,
            'retry_of_refund_id'
        );
    }

    public function retryAttempts(): HasMany
    {
        return $this->hasMany(
            Refund::class,
            'retry_of_refund_id'
        );
    }
}
