<?php

namespace App\Mail;

use App\Models\CareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareServicePartialConfirmationNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  string  $recipientType  'client' or 'pastor' - who is receiving this email
     * @param  string  $confirmedBy  'client' or 'pastor' - who confirmed
     */
    public function __construct(
        public CareService $appointment,
        public string $recipientType,
        public string $confirmedBy
    ) {
        $this->appointment->load(['pastor']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $confirmerName = $this->confirmedBy === 'client'
            ? $this->appointment->client_name
            : $this->appointment->pastor->first_name.' '.$this->appointment->pastor->last_name;

        return new Envelope(
            from: config('mail.from.address', 'noreply@icc-munich.de'),
            subject: "Confirmation reçue de {$confirmerName} - En attente de votre confirmation - ".config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Generate confirmation URL based on recipient type
        $confirmUrl = $this->recipientType === 'client'
            ? url('/api/care-service/confirm-by-client?token='.$this->appointment->client_confirmation_token)
            : url('/api/care-service/confirm-by-pastor?token='.$this->appointment->pastor_confirmation_token);

        return new Content(
            markdown: 'emails.care-service.partial-confirmation',
            with: [
                'appointment' => $this->appointment,
                'pastor' => $this->appointment->pastor,
                'recipientType' => $this->recipientType,
                'confirmedBy' => $this->confirmedBy,
                'confirmUrl' => $confirmUrl,
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
