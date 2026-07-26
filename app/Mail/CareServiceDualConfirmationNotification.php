<?php

namespace App\Mail;

use App\Models\CareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareServiceDualConfirmationNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  string  $recipientType  'client' or 'pastor'
     */
    public function __construct(
        public CareService $appointment,
        public string $recipientType
    ) {
        $this->appointment->load(['pastor']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address', 'noreply@bio-digital-sss.com'),
            subject: 'Rendez-vous confirmé - Les deux parties ont validé - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.care-service.dual-confirmation',
            with: [
                'appointment' => $this->appointment,
                'pastor' => $this->appointment->pastor,
                'recipientType' => $this->recipientType,
                'churchName' => config('app.name'),
                'churchWebsite' => 'https://www.bio-digital-sss.com',
                'churchEmail' => 'info@bio-digital-sss.com',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
