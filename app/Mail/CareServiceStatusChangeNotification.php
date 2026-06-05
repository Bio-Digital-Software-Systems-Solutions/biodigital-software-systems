<?php

namespace App\Mail;

use App\Models\CareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareServiceStatusChangeNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Status labels for email subject and content
     */
    private array $statusLabels = [
        'pending' => ['label' => 'en attente', 'action' => 'mis en attente'],
        'confirmed' => ['label' => 'confirmé', 'action' => 'confirmé'],
        'completed' => ['label' => 'terminé', 'action' => 'marqué comme terminé'],
        'cancelled' => ['label' => 'annulé', 'action' => 'annulé'],
        'no_show' => ['label' => 'non présenté', 'action' => 'marqué comme non présenté'],
    ];

    /**
     * Create a new message instance.
     *
     * @param  CareService  $appointment  The care service appointment
     * @param  string  $newStatus  The new status of the appointment
     * @param  string  $recipientType  Either 'client' or 'pastor'
     */
    public function __construct(
        public CareService $appointment,
        public string $newStatus,
        public string $recipientType = 'client'
    ) {
        $this->appointment->load(['pastor']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $statusLabel = $this->statusLabels[$this->newStatus]['label'] ?? $this->newStatus;

        $subject = $this->recipientType === 'pastor'
            ? "Rendez-vous pastoral {$statusLabel} - {$this->appointment->client_name}"
            : "Votre rendez-vous de soin pastoral a été {$statusLabel}";

        return new Envelope(
            from: config('mail.from.address', 'noreply@icc-munich.de'),
            subject: $subject.' - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $statusInfo = $this->statusLabels[$this->newStatus] ?? [
            'label' => $this->newStatus,
            'action' => $this->newStatus,
        ];

        return new Content(
            markdown: 'emails.care-service.status-change-notification',
            with: [
                'appointment' => $this->appointment,
                'pastor' => $this->appointment->pastor,
                'newStatus' => $this->newStatus,
                'statusLabel' => $statusInfo['label'],
                'statusAction' => $statusInfo['action'],
                'recipientType' => $this->recipientType,
                'recipientName' => $this->recipientType === 'pastor'
                    ? $this->appointment->pastor->first_name
                    : $this->appointment->client_name,
                'churchName' => config('app.church_name', config('app.name')),
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
