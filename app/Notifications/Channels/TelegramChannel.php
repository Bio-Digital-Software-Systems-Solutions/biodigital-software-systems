<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\TelegramMessage;
use App\Services\TelegramNotificationService;
use Illuminate\Notifications\Notification;

class TelegramChannel
{
    public function __construct(
        protected TelegramNotificationService $telegram
    ) {}

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Get the telegram chat_id from the notifiable entity
        $chatId = $notifiable->routeNotificationFor('telegram', $notification);

        if (! $chatId) {
            return;
        }

        // Check if Telegram notifications are enabled for this user
        if (method_exists($notifiable, 'telegram_notifications') && ! $notifiable->telegram_notifications) {
            return;
        }

        // Check the property directly if it exists
        if (property_exists($notifiable, 'telegram_notifications') && ! $notifiable->telegram_notifications) {
            return;
        }

        // If the notification uses the attribute, check it
        if (isset($notifiable->telegram_notifications) && ! $notifiable->telegram_notifications) {
            return;
        }

        // Get the telegram message from the notification
        $message = $notification->toTelegram($notifiable);

        if (! $message instanceof TelegramMessage) {
            return;
        }

        $this->telegram->sendMessage(
            $chatId,
            $message->getContent(),
            $message->getOptions()
        );
    }
}
