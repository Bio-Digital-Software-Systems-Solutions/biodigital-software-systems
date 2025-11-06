<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $message;
    public $recipient;
    public $sender;

    /**
     * Create a new message instance.
     */
    public function __construct(Message $message, User $recipient, User $sender)
    {
        $this->message = $message;
        $this->recipient = $recipient;
        $this->sender = $sender;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->message->subject
            ? $this->message->subject
            : 'Nouveau message de ' . $this->sender->first_name . ' ' . $this->sender->last_name;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.new-message',
            with: [
                'message' => $this->message,
                'recipient' => $this->recipient,
                'sender' => $this->sender,
                'messageUrl' => route('messages.index'),
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
