<?php

namespace App\Mail;

use App\Models\PastoralCare;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PastoralCareTransferNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  PastoralCare  $appointment  The pastoral care appointment
     * @param  User  $oldPastor  The pastor who previously had the appointment
     * @param  User  $newPastor  The pastor receiving the appointment
     * @param  string  $recipientType  Either 'client', 'old_pastor', or 'new_pastor'
     */
    public function __construct(
        public PastoralCare $appointment,
        public User $oldPastor,
        public User $newPastor,
        public string $recipientType = 'client'
    ) {
        $this->appointment->load(['pastor']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->recipientType) {
            'new_pastor' => "Nouveau rendez-vous pastoral transféré - {$this->appointment->client_name}",
            'old_pastor' => "Rendez-vous pastoral transféré à {$this->newPastor->first_name} {$this->newPastor->last_name}",
            default => 'Changement de responsable pour votre rendez-vous de soin pastoral',
        };

        return new Envelope(
            from: config('mail.from.address', 'noreply@icc-munich.de'),
            subject: $subject.' - ICC Munich',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $recipientName = match ($this->recipientType) {
            'new_pastor' => $this->newPastor->first_name,
            'old_pastor' => $this->oldPastor->first_name,
            default => $this->appointment->client_name,
        };

        return new Content(
            markdown: 'emails.pastoral-care.transfer-notification',
            with: [
                'appointment' => $this->appointment,
                'oldPastor' => $this->oldPastor,
                'newPastor' => $this->newPastor,
                'recipientType' => $this->recipientType,
                'recipientName' => $recipientName,
                'transferReason' => $this->appointment->transfer_reason,
                'churchName' => config('app.church_name', 'ICC Munich'),
                'churchEmail' => config('app.church_email', 'contact@icc-munich.de'),
                'churchPhone' => config('app.church_phone', '+49 89 123456'),
                'churchWebsite' => config('app.url', 'https://icc-munich.de'),
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
