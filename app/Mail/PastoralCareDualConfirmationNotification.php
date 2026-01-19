<?php

namespace App\Mail;

use App\Models\PastoralCare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PastoralCareDualConfirmationNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  string  $recipientType  'client' or 'pastor'
     */
    public function __construct(
        public PastoralCare $appointment,
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
            subject: 'Rendez-vous confirmé - Les deux parties ont validé - ICC Munich',
            from: config('mail.from.address', 'noreply@icc-munich.de'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pastoral-care.dual-confirmation',
            with: [
                'appointment' => $this->appointment,
                'pastor' => $this->appointment->pastor,
                'recipientType' => $this->recipientType,
                'churchName' => 'ICC Munich',
                'churchWebsite' => 'https://icc-munich.de',
                'churchEmail' => 'info@icc-munich.de',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
