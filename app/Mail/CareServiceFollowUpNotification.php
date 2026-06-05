<?php

namespace App\Mail;

use App\Models\CareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareServiceFollowUpNotification extends Mailable implements ShouldQueue
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
            from: config('mail.from.address', 'noreply@icc-munich.de'),
            replyTo: [$this->appointment->pastor->email],
            subject: 'Nouveau rendez-vous de suivi planifié - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Use token-based confirmation URL for dual confirmation system
        $confirmUrl = url('/api/care-service/confirm-by-client?token='.$this->appointment->client_confirmation_token);
        $cancelUrl = route('care-service.public.cancel', ['uuid' => $this->appointment->uuid]);

        return new Content(
            markdown: 'emails.care-service.follow-up-notification',
            with: [
                'appointment' => $this->appointment,
                'parentAppointment' => $this->parentAppointment,
                'pastor' => $this->appointment->pastor,
                'confirmUrl' => $confirmUrl,
                'cancelUrl' => $cancelUrl,
                'churchName' => config('app.name'),
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
