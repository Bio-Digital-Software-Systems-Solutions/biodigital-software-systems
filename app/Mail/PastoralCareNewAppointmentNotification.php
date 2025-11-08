<?php

namespace App\Mail;

use App\Models\PastoralCare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PastoralCareNewAppointmentNotification extends Mailable implements ShouldQueue
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
            subject: 'Nouveau rendez-vous de soin pastoral planifié - ICC Munich',
            from: config('mail.from.address', 'noreply@icc-munich.de'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pastoral-care.new-appointment-notification',
            with: [
                'appointment' => $this->appointment,
                'pastor' => $this->appointment->pastor,
                'dashboardUrl' => route('pastoral-care.index'),
                'appointmentUrl' => route('pastoral-care.show', ['pastoralCare' => $this->appointment->uuid]),
                'churchName' => 'ICC Munich',
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
