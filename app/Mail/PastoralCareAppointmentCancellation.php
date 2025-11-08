<?php

namespace App\Mail;

use App\Models\PastoralCare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PastoralCareAppointmentCancellation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public PastoralCare $appointment
    ) {
        $this->appointment->load(['pastor']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Annulation de votre rendez-vous de soin pastoral - ICC Munich',
            from: config('mail.from.address', 'noreply@icc-munich.de'),
            replyTo: $this->appointment->pastor->email,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pastoral-care.appointment-cancellation',
            with: [
                'appointment' => $this->appointment,
                'pastor' => $this->appointment->pastor,
                'bookingUrl' => route('pastoral-care.public.book'),
                'churchName' => 'ICC Munich',
                'churchWebsite' => 'https://icc-munich.de',
                'churchEmail' => 'info@icc-munich.de',
                'churchPhone' => '+49 89 123456789',
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
