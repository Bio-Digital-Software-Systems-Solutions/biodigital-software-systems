<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        /**
         * The 2FA code
         */
        protected string $code,
        /**
         * The expiration time in minutes
         */
        protected int $expirationMinutes
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
        $formattedCode = implode(' ', str_split($this->code, 4));

        return (new MailMessage)
            ->subject("Code de vérification - {$appName}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Vous avez demandé un code de vérification pour vous connecter à votre compte.')
            ->line('Votre code de vérification est :')
            ->line("**{$formattedCode}**")
            ->line("Ce code expire dans **{$this->expirationMinutes} minutes**.")
            ->line('Si vous n\'avez pas demandé ce code, veuillez ignorer cet email et vérifier la sécurité de votre compte.')
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
            'code' => $this->code,
            'expires_in_minutes' => $this->expirationMinutes,
        ];
    }
}
