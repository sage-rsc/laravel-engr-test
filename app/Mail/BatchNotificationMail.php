<?php

namespace App\Mail;

use App\Models\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BatchNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Batch $batch
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Claims Batch Ready for Processing',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.batch-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
