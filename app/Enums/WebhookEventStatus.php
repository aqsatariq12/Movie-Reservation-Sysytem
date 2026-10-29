<?php

namespace App\Enums;

enum WebhookEventStatus: string
{
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case Ignored = 'ignored';
}
