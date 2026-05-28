<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GracePeriodStarted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public int $penaltyPerDay = 10,
        public ?string $startedAt = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kamu Memasuki Masa Tenggang',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.grace-period-started',
        );
    }
}
