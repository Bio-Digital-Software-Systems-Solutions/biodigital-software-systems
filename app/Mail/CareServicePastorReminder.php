<?php

namespace App\Mail;

use App\Models\CareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareServicePastorReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public CareService $appointment
    ) {
        $this->appointment->load(['pastor', 'user']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address', 'noreply@icc-munich.de'),
            subject: 'Rappel : Rendez-vous pastoral demain - '.$this->appointment->client_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.care-service.pastor-reminder',
            with: [
                'appointment' => $this->appointment,
                'pastor' => $this->appointment->pastor,
                'churchName' => config('care_service.church_name', config('app.name')),
                'churchEmail' => config('care_service.church_email', 'info@icc-munich.de'),
                'churchPhone' => config('care_service.church_phone', '+49 89 123456789'),
                'appointmentUrl' => route('care-service.show', ['careService' => $this->appointment->uuid]),
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
