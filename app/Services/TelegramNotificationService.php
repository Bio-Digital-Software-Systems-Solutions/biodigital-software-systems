<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    /**
     * Telegram Bot API base URL
     */
    protected string $baseUrl = 'https://api.telegram.org/bot';

    /**
     * Check if Telegram notifications are enabled
     */
    public function isEnabled(): bool
    {
        return config('services.telegram.enabled', false)
            && ! empty(config('services.telegram.bot_token'));
    }

    /**
     * Get the bot token
     */
    protected function getBotToken(): string
    {
        return config('services.telegram.bot_token', '');
    }

    /**
     * Get the bot username for linking purposes
     */
    public function getBotUsername(): ?string
    {
        return config('services.telegram.bot_username');
    }

    /**
     * Get the link to start a conversation with the bot
     */
    public function getBotLink(): ?string
    {
        $username = $this->getBotUsername();

        return $username ? "https://t.me/{$username}" : null;
    }

    /**
     * Send a message to a Telegram chat
     */
    public function sendMessage(string $chatId, string $message, array $options = []): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::post($this->baseUrl.$this->getBotToken().'/sendMessage', array_merge([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ], $options));

            if ($response->successful() && $response->json('ok')) {
                Log::info('Telegram message sent successfully', [
                    'chat_id' => $chatId,
                    'message_id' => $response->json('result.message_id'),
                ]);

                return true;
            }

            Log::error('Failed to send Telegram message', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Telegram message sending exception', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send appointment reminder to a participant via Telegram
     */
    public function sendReminder(Appointment $appointment, User $participant): bool
    {
        if (! $this->isEnabled() || empty($participant->telegram_chat_id)) {
            return false;
        }

        if (! $participant->telegram_notifications) {
            return false;
        }

        $message = $this->buildReminderMessage($appointment, $participant);

        return $this->sendMessage($participant->telegram_chat_id, $message);
    }

    /**
     * Send appointment reminder to the organizer via Telegram
     */
    public function sendOrganizerReminder(Appointment $appointment): bool
    {
        if (! $this->isEnabled() || ! $appointment->organizer?->telegram_chat_id) {
            return false;
        }

        if (! $appointment->organizer->telegram_notifications) {
            return false;
        }

        $message = $this->buildOrganizerReminderMessage($appointment);

        return $this->sendMessage($appointment->organizer->telegram_chat_id, $message);
    }

    /**
     * Send confirmation notification via Telegram
     */
    public function sendConfirmation(Appointment $appointment, User $participant): bool
    {
        if (! $this->isEnabled() || empty($participant->telegram_chat_id)) {
            return false;
        }

        if (! $participant->telegram_notifications) {
            return false;
        }

        $message = $this->buildConfirmationMessage($appointment, $participant);

        return $this->sendMessage($participant->telegram_chat_id, $message);
    }

    /**
     * Send cancellation notification via Telegram
     */
    public function sendCancellation(Appointment $appointment, User $participant): bool
    {
        if (! $this->isEnabled() || empty($participant->telegram_chat_id)) {
            return false;
        }

        if (! $participant->telegram_notifications) {
            return false;
        }

        $message = $this->buildCancellationMessage($appointment, $participant);

        return $this->sendMessage($participant->telegram_chat_id, $message);
    }

    /**
     * Send invitation notification via Telegram
     */
    public function sendInvitation(Appointment $appointment, User $participant, string $confirmUrl, string $declineUrl): bool
    {
        if (! $this->isEnabled() || empty($participant->telegram_chat_id)) {
            return false;
        }

        if (! $participant->telegram_notifications) {
            return false;
        }

        $message = $this->buildInvitationMessage($appointment, $participant, $confirmUrl, $declineUrl);

        return $this->sendMessage($participant->telegram_chat_id, $message);
    }

    /**
     * Send update notification via Telegram
     */
    public function sendUpdate(Appointment $appointment, User $participant, array $changes = []): bool
    {
        if (! $this->isEnabled() || empty($participant->telegram_chat_id)) {
            return false;
        }

        if (! $participant->telegram_notifications) {
            return false;
        }

        $message = $this->buildUpdateMessage($appointment, $participant, $changes);

        return $this->sendMessage($participant->telegram_chat_id, $message);
    }

    /**
     * Build reminder message for participant
     */
    protected function buildReminderMessage(Appointment $appointment, User $participant): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $endTime = $appointment->end_datetime->format('H:i');
        $organizer = $appointment->organizer->first_name.' '.$appointment->organizer->last_name;
        $appName = config('app.name', 'ICC Munich');

        $locationType = match ($appointment->meeting_mode ?? 'in_person') {
            'online' => 'En ligne',
            'hybrid' => 'Hybride',
            default => 'En presentiel',
        };

        $message = "<b>Rappel de rendez-vous - {$appName}</b>\n\n";
        $message .= "Bonjour {$participant->first_name},\n\n";
        $message .= "Votre rendez-vous est prevu pour <b>demain</b>.\n\n";
        $message .= "<b>{$appointment->title}</b>\n\n";
        $message .= "Date: {$date}\n";
        $message .= "Heure: {$time} - {$endTime}\n";
        $message .= "Duree: {$appointment->duration_minutes} minutes\n";
        $message .= "Organisateur: {$organizer}\n";
        $message .= "Type: {$locationType}\n";

        if (in_array($appointment->meeting_mode, ['online', 'hybrid']) && $appointment->meeting_link) {
            $message .= "\nLien de reunion: {$appointment->meeting_link}\n";
        }

        if ($appointment->location && $appointment->meeting_mode !== 'online') {
            $message .= "\nLieu: {$appointment->location}\n";
        }

        $message .= "\nEn cas d'empechement, veuillez prevenir l'organisateur.\n\n";
        $message .= 'A bientot !';

        return $message;
    }

    /**
     * Build reminder message for organizer
     */
    protected function buildOrganizerReminderMessage(Appointment $appointment): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $participantsCount = $appointment->confirmedParticipants()->count();
        $appName = config('app.name', 'ICC Munich');

        $message = "<b>Rappel - Votre rendez-vous de demain</b>\n\n";
        $message .= "<b>{$appointment->title}</b>\n\n";
        $message .= "Date: {$date}\n";
        $message .= "Heure: {$time}\n";
        $message .= "Participants confirmes: {$participantsCount}\n";

        if ($appointment->location) {
            $message .= "Lieu: {$appointment->location}\n";
        }

        $message .= "\nConsultez vos emails pour plus de details.";

        return $message;
    }

    /**
     * Build confirmation message
     */
    protected function buildConfirmationMessage(Appointment $appointment, User $participant): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $organizer = $appointment->organizer->first_name.' '.$appointment->organizer->last_name;
        $appName = config('app.name', 'ICC Munich');

        $message = "<b>Confirmation de rendez-vous - {$appName}</b>\n\n";
        $message .= "Bonjour {$participant->first_name},\n\n";
        $message .= "Votre rendez-vous est confirme !\n\n";
        $message .= "<b>{$appointment->title}</b>\n\n";
        $message .= "Date: {$date}\n";
        $message .= "Heure: {$time}\n";
        $message .= "Organisateur: {$organizer}\n\n";
        $message .= "Vous recevrez un rappel 24h avant le rendez-vous.\n\n";
        $message .= 'A bientot !';

        return $message;
    }

    /**
     * Build cancellation message
     */
    protected function buildCancellationMessage(Appointment $appointment, User $participant): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $appName = config('app.name', 'ICC Munich');

        $message = "<b>Annulation de rendez-vous - {$appName}</b>\n\n";
        $message .= "Bonjour {$participant->first_name},\n\n";
        $message .= "Nous vous informons que le rendez-vous suivant a ete annule :\n\n";
        $message .= "<b>{$appointment->title}</b>\n";
        $message .= "Date prevue: {$date} a {$time}\n\n";
        $message .= 'Nous vous prions de nous excuser pour ce desagrement.';

        return $message;
    }

    /**
     * Build invitation message
     */
    protected function buildInvitationMessage(Appointment $appointment, User $participant, string $confirmUrl, string $declineUrl): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $endTime = $appointment->end_datetime->format('H:i');
        $organizer = $appointment->organizer->first_name.' '.$appointment->organizer->last_name;
        $appName = config('app.name', 'ICC Munich');

        $locationType = match ($appointment->meeting_mode ?? 'in_person') {
            'online' => 'En ligne',
            'hybrid' => 'Hybride',
            default => 'En presentiel',
        };

        $message = "<b>Invitation a un rendez-vous - {$appName}</b>\n\n";
        $message .= "Bonjour {$participant->first_name},\n\n";
        $message .= "Vous etes invite(e) a un rendez-vous :\n\n";
        $message .= "<b>{$appointment->title}</b>\n\n";
        $message .= "Date: {$date}\n";
        $message .= "Heure: {$time} - {$endTime}\n";
        $message .= "Duree: {$appointment->duration_minutes} minutes\n";
        $message .= "Organisateur: {$organizer}\n";
        $message .= "Type: {$locationType}\n";

        if ($appointment->location && $appointment->meeting_mode !== 'online') {
            $message .= "Lieu: {$appointment->location}\n";
        }

        if ($appointment->description) {
            $message .= "\nDescription: ".strip_tags($appointment->description)."\n";
        }

        $message .= "\nPour repondre a cette invitation, cliquez sur les liens ci-dessous :\n";
        $message .= "Accepter: {$confirmUrl}\n";
        $message .= "Decliner: {$declineUrl}\n";

        return $message;
    }

    /**
     * Build update message
     */
    protected function buildUpdateMessage(Appointment $appointment, User $participant, array $changes = []): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $appName = config('app.name', 'ICC Munich');

        $message = "<b>Mise a jour de rendez-vous - {$appName}</b>\n\n";
        $message .= "Bonjour {$participant->first_name},\n\n";
        $message .= "Le rendez-vous suivant a ete mis a jour :\n\n";
        $message .= "<b>{$appointment->title}</b>\n\n";
        $message .= "Date: {$date}\n";
        $message .= "Heure: {$time}\n";

        if (! empty($changes)) {
            $message .= "\nModifications :\n";
            foreach ($changes as $field => $change) {
                $fieldLabel = $this->getFieldLabel($field);
                $message .= "- {$fieldLabel}: {$change['old']} -> {$change['new']}\n";
            }
        }

        $message .= "\nConsultez vos emails pour plus de details.";

        return $message;
    }

    /**
     * Get human-readable field label
     */
    protected function getFieldLabel(string $field): string
    {
        return match ($field) {
            'title' => 'Titre',
            'start_datetime' => 'Date/Heure de debut',
            'end_datetime' => 'Date/Heure de fin',
            'location' => 'Lieu',
            'meeting_mode' => 'Mode de reunion',
            'meeting_link' => 'Lien de reunion',
            'description' => 'Description',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    /**
     * Send reminders to all participants with Telegram
     *
     * @return array{sent: int, errors: int}
     */
    public function sendRemindersToAllParticipants(Appointment $appointment): array
    {
        $results = ['sent' => 0, 'errors' => 0];

        $participants = $appointment->getParticipantsWithTelegram();

        foreach ($participants as $participant) {
            if ($this->sendReminder($appointment, $participant)) {
                $results['sent']++;
            } else {
                $results['errors']++;
            }
        }

        return $results;
    }

    /**
     * Verify a webhook URL (for future webhook implementation)
     */
    public function setWebhook(string $url): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::post($this->baseUrl.$this->getBotToken().'/setWebhook', [
                'url' => $url,
            ]);

            return $response->successful() && $response->json('ok');
        } catch (\Exception $e) {
            Log::error('Failed to set Telegram webhook', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get bot info to verify token is valid
     */
    public function getBotInfo(): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::get($this->baseUrl.$this->getBotToken().'/getMe');

            if ($response->successful() && $response->json('ok')) {
                return $response->json('result');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get Telegram bot info', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get updates (messages sent to bot) - useful for getting chat_id
     */
    public function getUpdates(int $offset = 0): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        try {
            $response = Http::get($this->baseUrl.$this->getBotToken().'/getUpdates', [
                'offset' => $offset,
            ]);

            if ($response->successful() && $response->json('ok')) {
                return $response->json('result', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get Telegram updates', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
