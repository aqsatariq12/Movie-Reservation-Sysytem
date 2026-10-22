<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'payment_id',
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
            'amount_cents' => 'integer',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
