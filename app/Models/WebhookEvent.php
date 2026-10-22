<?php

namespace App\Models;

use App\Enums\WebhookEventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'webhook_events';

    protected $fillable = [
        'provider_event_id',
        'event_type',
        'status',
        'booking_id',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_id' => 'integer',
            'status' => WebhookEventStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
