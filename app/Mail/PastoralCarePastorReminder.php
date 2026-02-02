<?php

namespace App\Mail;

use App\Models\PastoralCare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PastoralCarePastorReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public PastoralCare $appointment
    ) {
        $this->appointment->load(['pastor', 'user']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rappel : Rendez-vous pastoral demain - '.$this->appointment->client_name,
            from: config('mail.from.address', 'noreply@icc-munich.de'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pastoral-care.pastor-reminder',
            with: [
                'appointment' => $this->appointment,
                'pastor' => $this->appointment->pastor,
                'churchName' => config('pastoral_care.church_name', 'ICC Munich'),
                'churchEmail' => config('pastoral_care.church_email', 'info@icc-munich.de'),
                'churchPhone' => config('pastoral_care.church_phone', '+49 89 123456789'),
                'appointmentUrl' => route('pastoral-care.show', ['pastoralCare' => $this->appointment->uuid]),
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
