<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ticket Scanned</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.6;">
    <h2>CineBook — Ticket Scan Successful</h2>

    <p>Hello {{ $ticketScan->ticket?->bookingSeat?->booking?->user?->name ?? 'Customer' }},</p>

    <p>Your ticket has been successfully scanned, and entry has been accepted.</p>

    <p>
        <strong>Movie:</strong>
        {{ $ticketScan->ticket?->bookingSeat?->booking?->showtime?->movie?->title ?? 'N/A' }}
    </p>

    <p>
        <strong>Showtime:</strong>
        {{ $ticketScan->ticket?->bookingSeat?->booking?->showtime?->starts_at?->format('d M Y, h:i A') ?? 'N/A' }}
    </p>

    <p>
        <strong>Seat:</strong>
        @php
            $seat = $ticketScan->ticket?->bookingSeat?->seat;
        @endphp
        {{ $seat ? ($seat->row_label ?? '') . ($seat->seat_number ?? '') : 'N/A' }}
    </p>

    <p>
        <strong>Scanned at:</strong>
        {{ $ticketScan->scanned_at?->format('d M Y, h:i A') ?? 'N/A' }}
    </p>

    <p>Thank you for choosing CineBook.</p>
</body>
</html>
