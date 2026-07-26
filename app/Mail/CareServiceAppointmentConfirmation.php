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

class CareServiceAppointmentConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public CareService $appointment
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
            replyTo: $this->appointment->pastor->email,
            subject: 'Confirmation de votre rendez-vous de soin pastoral - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.care-service.appointment-confirmation',
            with: [
                'appointment' => $this->appointment,
                'pastor' => $this->appointment->pastor,
                'confirmUrl' => route('care-service.public.confirm', ['uuid' => $this->appointment->uuid]),
                'cancelUrl' => route('care-service.public.cancel', ['uuid' => $this->appointment->uuid]),
                'churchName' => config('app.name'),
                'churchWebsite' => 'https://www.bio-digital-sss.com',
                'churchEmail' => 'info@bio-digital-sss.com',
                'churchPhone' => '+49 89 123456789',
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
