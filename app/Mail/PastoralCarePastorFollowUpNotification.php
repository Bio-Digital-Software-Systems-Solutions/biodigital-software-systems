<?php

namespace App\Mail;

use App\Models\PastoralCare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PastoralCarePastorFollowUpNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public PastoralCare $appointment,
        public PastoralCare $parentAppointment
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
            from: config('mail.from.address', 'noreply@icc-munich.de'),
            subject: 'Nouveau rendez-vous de suivi créé - Confirmation requise - ICC Munich',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Generate pastor confirmation URL with token
        $confirmUrl = url('/api/pastoral-care/confirm-by-pastor?token='.$this->appointment->pastor_confirmation_token);
        $cancelUrl = route('pastoral-care.public.cancel', ['uuid' => $this->appointment->uuid]);

        return new Content(
            markdown: 'emails.pastoral-care.pastor-follow-up-notification',
            with: [
                'appointment' => $this->appointment,
                'parentAppointment' => $this->parentAppointment,
                'pastor' => $this->appointment->pastor,
                'confirmUrl' => $confirmUrl,
                'cancelUrl' => $cancelUrl,
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
