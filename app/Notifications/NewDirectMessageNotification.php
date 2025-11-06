<?php

namespace App\Notifications;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDirectMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $chatMessage;
    public $sender;

    /**
     * Create a new notification instance.
     */
    public function __construct(ChatMessage $chatMessage, User $sender)
    {
        $this->chatMessage = $chatMessage;
        $this->sender = $sender;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $chatUrl = route('chat.index');
        $senderName = $this->sender->first_name . ' ' . $this->sender->last_name;

        // Truncate message content for email preview
        $messagePreview = strlen($this->chatMessage->content) > 100
            ? substr($this->chatMessage->content, 0, 100) . '...'
            : $this->chatMessage->content;

        return (new MailMessage)
            ->subject("💬 Nouveau message de {$senderName}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Vous avez reçu un nouveau message de **{$senderName}** :")
            ->line("_{$messagePreview}_")
            ->line("Connectez-vous à votre messagerie pour lire le message complet et répondre.")
            ->action('📱 Ouvrir la messagerie', $chatUrl)
            ->line('Merci de consulter votre messagerie !')
            ->salutation('L\'équipe ICC München');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $senderName = $this->sender->first_name . ' ' . $this->sender->last_name;

        return [
            'type' => 'new_message',
            'title' => "Nouveau message de {$senderName}",
            'message' => "Vous avez reçu un nouveau message dans votre messagerie.",
            'sender_id' => $this->sender->id,
            'sender_name' => $senderName,
            'chat_message_id' => $this->chatMessage->id,
            'room_id' => $this->chatMessage->room_id,
            'action_url' => route('chat.index'),
            'created_at' => now(),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}