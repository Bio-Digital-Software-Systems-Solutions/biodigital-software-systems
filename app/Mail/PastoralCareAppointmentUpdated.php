<?php

namespace App\Mail;

use App\Models\PastoralCare;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PastoralCareAppointmentUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public PastoralCare $appointment,
        public array $changes,
        public string $recipientType = 'client'
    ) {
        $this->appointment->load(['pastor', 'user']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rendez-vous de soin pastoral modifie - ICC Munich',
            from: config('mail.from.address', 'noreply@icc-munich.de'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pastoral-care.appointment-updated',
            with: [
                'appointment' => $this->appointment,
                'changes' => $this->changes,
                'recipientType' => $this->recipientType,
                'pastor' => $this->appointment->pastor,
                'fieldLabels' => $this->getFieldLabels(),
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

    /**
     * Get human-readable field labels.
     */
    protected function getFieldLabels(): array
    {
        return [
            'appointment_date' => 'Date du rendez-vous',
            'appointment_time' => 'Heure du rendez-vous',
            'duration_minutes' => 'Duree (minutes)',
            'location_type' => 'Type de lieu',
            'zoom_link' => 'Lien Zoom',
            'pastor_id' => 'Pasteur/Conseiller',
            'status' => 'Statut',
        ];
    }
}
