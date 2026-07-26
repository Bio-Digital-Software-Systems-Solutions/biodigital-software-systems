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

class CareServicePastorFollowUpNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public CareService $appointment,
        public CareService $parentAppointment
    ) {
        $this->appointment->load(['pastor']);
        $this->parentAppointment->load(['pastor']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address', 'noreply@bio-digital-sss.com'),
            subject: 'Nouveau rendez-vous de suivi créé - Confirmation requise - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Generate pastor confirmation URL with token
        $confirmUrl = url('/api/care-service/confirm-by-pastor?token='.$this->appointment->pastor_confirmation_token);
        $cancelUrl = route('care-service.public.cancel', ['uuid' => $this->appointment->uuid]);

        return new Content(
            markdown: 'emails.care-service.pastor-follow-up-notification',
            with: [
                'appointment' => $this->appointment,
                'parentAppointment' => $this->parentAppointment,
                'pastor' => $this->appointment->pastor,
                'confirmUrl' => $confirmUrl,
                'cancelUrl' => $cancelUrl,
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
