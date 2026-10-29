<?php

namespace App\Mail;

use App\Models\TicketScan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketScannedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TicketScan $ticketScan) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'CineBook Ticket Successfully Scanned'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-scanned',
            with: ['ticketScan' => $this->ticketScan]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
