<?php

namespace App\Notifications;

use App\Models\BlockedLoginAttempt;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BlockedLoginAttemptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BlockedLoginAttempt $attempt,
        public User $blockedUser
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        return (new MailMessage)
            ->subject("⚠️ Tentative de connexion bloquée - {$appName}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Un utilisateur avec un compte bloqué a tenté de se connecter à la plateforme.')
            ->line('**Détails de la tentative :**')
            ->line("• **Utilisateur** : {$this->blockedUser->full_name}")
            ->line("• **Email** : {$this->attempt->email}")
            ->line("• **Adresse IP** : {$this->attempt->ip_address}")
            ->line("• **Date/Heure** : {$this->attempt->created_at->format('d/m/Y à H:i:s')}")
            ->action('Voir les détails', route('user-management.show', ['user' => $this->blockedUser->uuid]))
            ->line('Cette notification est envoyée automatiquement pour des raisons de sécurité.')
            ->salutation("L'équipe {$appName}");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'attempt_id' => $this->attempt->id,
            'blocked_user_id' => $this->blockedUser->id,
            'email' => $this->attempt->email,
            'ip_address' => $this->attempt->ip_address,
        ];
    }
}
