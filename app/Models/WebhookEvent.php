<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
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
            'processed_at' => 'datetime',
        ];
    }
}