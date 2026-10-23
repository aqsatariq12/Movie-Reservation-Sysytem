<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FailedJobPresenter
{
    /** Short job class name => what the failure means in plain words. */
    private const OUTCOMES = [
        'SendBookingConfirmation' => 'Email not sent',
        'ProcessRefund'           => 'Provider retry needed',
        'ProcessStripeWebhook'    => 'Event awaiting retry',
    ];

    /** Short job class name => page title on the detail screen. */
    private const TITLES = [
        'SendBookingConfirmation' => 'Email job · delivery failed',
        'ProcessRefund'           => 'Refund job · processing failed',
        'ProcessStripeWebhook'    => 'Webhook job · processing failed',
    ];

    public static function row(object $row): array
    {
        $payload = json_decode($row->payload, true) ?: [];
        $fullClass = $payload['displayName'] ?? 'Unknown job';
        $class = class_basename($fullClass);

        // First line of a PHP exception string is "Class: message in /path/file.php:123"
        $firstLine = Str::before((string) $row->exception, "\n");
        $exceptionClass = class_basename(Str::before($firstLine, ':'));
        $message = Str::beforeLast(Str::after($firstLine, ': '), ' in '); // drops the file path

        // Booking / refund / payment reference, if the job carries one.
        preg_match('/\b(?:CB-\d{6}-\d{4}|RF-[A-Z0-9]+|PAY-[A-Z0-9]+)\b/', (string) $row->payload, $match);
        $reference = $match[0] ?? null;

        return [
            'id'              => $row->id,
            'uuid'            => $row->uuid,
            'queue'           => $row->queue,
            'attempts'        => $payload['attempts'] ?? null,
            'failed_at'       => Carbon::parse($row->failed_at),
            'class'           => $class,
            'full_class'      => $fullClass,
            'title'           => self::TITLES[$class] ?? ($class . ' · job failed'),
            'reference'       => $reference,
            'summary'         => trim(($reference ? $reference . ' · ' : '') . (self::OUTCOMES[$class] ?? 'Needs review')),
            'exception_class' => $exceptionClass,
            'message'         => Str::limit(self::sanitize($message), 300),
        ];
    }

    /** Masks anything that could leak credentials, personal data or server paths. */
    private static function sanitize(string $text): string
    {
        $text = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '[email]', $text);
        $text = preg_replace('/\b(?:sk|pk|rk|whsec)_[A-Za-z0-9_]+/', '[redacted]', $text);
        $text = preg_replace('/[A-Za-z0-9_\-]{32,}/', '[redacted]', $text);
        $text = preg_replace('#(?:[A-Za-z]:)?[\\\\/][\w.\\\\/\-]+\.php(?::\d+)?#', '[path]', $text);

        return $text;
    }
}