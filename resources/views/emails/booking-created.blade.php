<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBook Booking Created</title>
</head>
<body style="margin:0; padding:24px; background:#f3f4f6; font-family:Arial,sans-serif; color:#1f2937;">
    <div style="max-width:600px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden;">
        <div style="background:#111827; color:#ffffff; padding:24px;">
            <h1 style="margin:0;">CineBook</h1>
            <p style="margin:8px 0 0;">Your booking has been created</p>
        </div>

        <div style="padding:24px;">
            <p>Hello {{ $booking->user->name ?? 'Customer' }},</p>

            <p>Your booking has been created successfully. Payment is still pending, so your tickets are not confirmed yet.</p>

            <div style="background:#f9fafb; padding:16px; border-radius:8px;">
                <p><strong>Booking reference:</strong> {{ $booking->booking_reference }}</p>
                <p><strong>Booking status:</strong> {{ is_object($booking->status) ? $booking->status->value : $booking->status }}</p>
                <p><strong>Amount:</strong> {{ strtoupper($booking->currency) }} {{ number_format($booking->total_amount_cents / 100, 2) }}</p>

                @if($booking->showtime)
                    <p><strong>Movie:</strong> {{ $booking->showtime->movie->title ?? 'Movie details unavailable' }}</p>
                    <p><strong>Showtime:</strong> {{ $booking->showtime->starts_at?->format('d M Y, h:i A') ?? 'Not available' }}</p>
                @endif

                @if($booking->bookingSeats->isNotEmpty())
                    <p><strong>Seats:</strong>
                        {{ $booking->bookingSeats->map(fn ($seat) => $seat->seat ? $seat->seat->row_label . $seat->seat->seat_number : null)->filter()->implode(', ') }}
                    </p>
                @endif
            </div>

            <p>Please complete your payment to confirm your booking.</p>

            <p style="margin-top:28px;">Thank you for choosing CineBook!</p>
            <p style="color:#6b7280; font-size:12px;">This is an automatic email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
