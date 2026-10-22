<?php

namespace App\Enums;

enum WebhookEventStatus: string
{
    case Processing = 'processing';
    case Processed = 'processed';
    case Ignored = 'ignored';
    case Failed = 'failed';
}
