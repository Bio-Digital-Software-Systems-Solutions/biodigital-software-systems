<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppointmentSmsNotificationService
{
    /**
     * Check if SMS notifications are enabled
     */
    public function isSmsEnabled(): bool
    {
        return config('services.sms.enabled', false)
            && ! empty(config('services.twilio.sid'))
            && ! empty(config('services.twilio.token'))
            && ! empty(config('services.twilio.from'));
    }

    /**
     * Check if WhatsApp notifications are enabled
     */
    public function isWhatsAppEnabled(): bool
    {
        return config('services.whatsapp.enabled', false)
            && ! empty(config('services.twilio.sid'))
            && ! empty(config('services.twilio.token'))
            && ! empty(config('services.twilio.whatsapp_from'));
    }

    /**
     * Send SMS reminder for an appointment to a participant
     */
    public function sendSmsReminder(Appointment $appointment, User $participant): bool
    {
        if (! $this->isSmsEnabled() || empty($participant->phone_number)) {
            return false;
        }

        $message = $this->buildSmsReminderMessage($appointment, $participant);

        return $this->sendTwilioSms($participant->phone_number, $message);
    }

    /**
     * Send WhatsApp reminder for an appointment to a participant
     */
    public function sendWhatsAppReminder(Appointment $appointment, User $participant): bool
    {
        if (! $this->isWhatsAppEnabled() || empty($participant->phone_number)) {
            return false;
        }

        $message = $this->buildWhatsAppReminderMessage($appointment, $participant);

        return $this->sendTwilioWhatsApp($participant->phone_number, $message);
    }

    /**
     * Send SMS to organizer for reminder
     */
    public function sendSmsOrganizerReminder(Appointment $appointment): bool
    {
        if (! $this->isSmsEnabled() || ! $appointment->organizer?->phone_number) {
            return false;
        }

        $message = $this->buildSmsOrganizerReminderMessage($appointment);

        return $this->sendTwilioSms($appointment->organizer->phone_number, $message);
    }

    /**
     * Send generic SMS
     */
    public function sendSms(string $to, string $message): bool
    {
        if (! $this->isSmsEnabled()) {
            return false;
        }

        return $this->sendTwilioSms($to, $message);
    }

    /**
     * Send generic WhatsApp message
     */
    public function sendWhatsApp(string $to, string $message): bool
    {
        if (! $this->isWhatsAppEnabled()) {
            return false;
        }

        return $this->sendTwilioWhatsApp($to, $message);
    }

    /**
     * Build SMS reminder message for participant
     */
    protected function buildSmsReminderMessage(Appointment $appointment, User $participant): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $organizer = $appointment->organizer->first_name.' '.$appointment->organizer->last_name;
        $appName = config('app.name', 'ICC Munich');

        $locationType = match ($appointment->meeting_mode ?? 'in_person') {
            'online' => 'en ligne',
            'hybrid' => 'hybride',
            default => 'en présentiel',
        };

        return "Rappel {$appName}: RDV \"{$appointment->title}\" demain {$date} à {$time} ({$locationType}) avec {$organizer}. "
            ."En cas d'empêchement, contactez l'organisateur.";
    }

    /**
     * Build WhatsApp reminder message for participant (can be more detailed)
     */
    protected function buildWhatsAppReminderMessage(Appointment $appointment, User $participant): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $endTime = $appointment->end_datetime->format('H:i');
        $organizer = $appointment->organizer->first_name.' '.$appointment->organizer->last_name;
        $appName = config('app.name', 'ICC Munich');

        $locationType = match ($appointment->meeting_mode ?? 'in_person') {
            'online' => 'En ligne',
            'hybrid' => 'Hybride (présentiel + visio)',
            default => 'En présentiel',
        };

        $message = "Rappel de rendez-vous - {$appName}\n\n";
        $message .= "Bonjour {$participant->first_name},\n\n";
        $message .= "Votre rendez-vous est prévu pour *demain*.\n\n";
        $message .= "*{$appointment->title}*\n\n";
        $message .= "Date: {$date}\n";
        $message .= "Heure: {$time} - {$endTime}\n";
        $message .= "Durée: {$appointment->duration_minutes} minutes\n";
        $message .= "Organisateur: {$organizer}\n";
        $message .= "Type: {$locationType}\n";

        if (in_array($appointment->meeting_mode, ['online', 'hybrid']) && $appointment->meeting_link) {
            $message .= "\nLien de réunion: {$appointment->meeting_link}\n";
        }

        if ($appointment->location && $appointment->meeting_mode !== 'online') {
            $message .= "\nLieu: {$appointment->location}\n";
        }

        $message .= "\nEn cas d'empêchement, veuillez prévenir l'organisateur.\n\n";
        $message .= 'À bientôt !';

        return $message;
    }

    /**
     * Build SMS reminder message for organizer
     */
    protected function buildSmsOrganizerReminderMessage(Appointment $appointment): string
    {
        $date = $appointment->start_datetime->format('d/m');
        $time = $appointment->start_datetime->format('H:i');
        $participantsCount = $appointment->participants()->count();

        return "Rappel: RDV \"{$appointment->title}\" demain {$date} à {$time} avec {$participantsCount} participant(s). Consultez vos emails pour plus de détails.";
    }

    /**
     * Send SMS via Twilio
     */
    protected function sendTwilioSms(string $to, string $message): bool
    {
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from = config('services.twilio.from');

            // Normalize phone number
            $to = $this->normalizePhoneNumber($to);

            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info('Appointment SMS sent successfully', [
                    'to' => $to,
                    'sid' => $response->json('sid'),
                ]);

                return true;
            }

            Log::error('Failed to send appointment SMS', [
                'to' => $to,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Appointment SMS sending exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send WhatsApp message via Twilio
     */
    protected function sendTwilioWhatsApp(string $to, string $message): bool
    {
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from = config('services.twilio.whatsapp_from');

            // Normalize phone number and add whatsapp: prefix
            $to = 'whatsapp:'.$this->normalizePhoneNumber($to);
            $from = 'whatsapp:'.$from;

            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info('Appointment WhatsApp message sent successfully', [
                    'to' => $to,
                    'sid' => $response->json('sid'),
                ]);

                return true;
            }

            Log::error('Failed to send appointment WhatsApp message', [
                'to' => $to,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Appointment WhatsApp sending exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Normalize phone number to E.164 format
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // If doesn't start with +, assume German number
        if (! str_starts_with($phone, '+')) {
            // Remove leading 0 if present
            $phone = ltrim($phone, '0');
            // Add German country code
            $phone = '+49'.$phone;
        }

        return $phone;
    }

    /**
     * Send confirmation notification via SMS
     */
    public function sendSmsConfirmation(Appointment $appointment, User $participant): bool
    {
        if (! $this->isSmsEnabled() || empty($participant->phone_number)) {
            return false;
        }

        $message = $this->buildSmsConfirmationMessage($appointment);

        return $this->sendTwilioSms($participant->phone_number, $message);
    }

    /**
     * Send confirmation notification via WhatsApp
     */
    public function sendWhatsAppConfirmation(Appointment $appointment, User $participant): bool
    {
        if (! $this->isWhatsAppEnabled() || empty($participant->phone_number)) {
            return false;
        }

        $message = $this->buildWhatsAppConfirmationMessage($appointment, $participant);

        return $this->sendTwilioWhatsApp($participant->phone_number, $message);
    }

    /**
     * Build confirmation message for SMS
     */
    protected function buildSmsConfirmationMessage(Appointment $appointment): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $appName = config('app.name', 'ICC Munich');

        return "{$appName}: Votre RDV \"{$appointment->title}\" du {$date} à {$time} est confirmé. Merci !";
    }

    /**
     * Build confirmation message for WhatsApp
     */
    protected function buildWhatsAppConfirmationMessage(Appointment $appointment, User $participant): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $organizer = $appointment->organizer->first_name.' '.$appointment->organizer->last_name;
        $appName = config('app.name', 'ICC Munich');

        $message = "Confirmation de rendez-vous - {$appName}\n\n";
        $message .= "Bonjour {$participant->first_name},\n\n";
        $message .= "Votre rendez-vous est confirmé !\n\n";
        $message .= "*{$appointment->title}*\n\n";
        $message .= "Date: {$date}\n";
        $message .= "Heure: {$time}\n";
        $message .= "Organisateur: {$organizer}\n\n";
        $message .= "Vous recevrez un rappel 24h avant le rendez-vous.\n\n";
        $message .= 'À bientôt !';

        return $message;
    }

    /**
     * Send cancellation notification via SMS
     */
    public function sendSmsCancellation(Appointment $appointment, User $participant): bool
    {
        if (! $this->isSmsEnabled() || empty($participant->phone_number)) {
            return false;
        }

        $message = $this->buildSmsCancellationMessage($appointment);

        return $this->sendTwilioSms($participant->phone_number, $message);
    }

    /**
     * Send cancellation notification via WhatsApp
     */
    public function sendWhatsAppCancellation(Appointment $appointment, User $participant): bool
    {
        if (! $this->isWhatsAppEnabled() || empty($participant->phone_number)) {
            return false;
        }

        $message = $this->buildWhatsAppCancellationMessage($appointment, $participant);

        return $this->sendTwilioWhatsApp($participant->phone_number, $message);
    }

    /**
     * Build cancellation message for SMS
     */
    protected function buildSmsCancellationMessage(Appointment $appointment): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $appName = config('app.name', 'ICC Munich');

        return "{$appName}: Le RDV \"{$appointment->title}\" du {$date} à {$time} a été annulé.";
    }

    /**
     * Build cancellation message for WhatsApp
     */
    protected function buildWhatsAppCancellationMessage(Appointment $appointment, User $participant): string
    {
        $date = $appointment->start_datetime->format('d/m/Y');
        $time = $appointment->start_datetime->format('H:i');
        $appName = config('app.name', 'ICC Munich');

        $message = "Annulation de rendez-vous - {$appName}\n\n";
        $message .= "Bonjour {$participant->first_name},\n\n";
        $message .= "Nous vous informons que le rendez-vous suivant a été annulé :\n\n";
        $message .= "*{$appointment->title}*\n";
        $message .= "Date prévue: {$date} à {$time}\n\n";
        $message .= 'Nous vous prions de nous excuser pour ce désagrément.';

        return $message;
    }

    /**
     * Send reminder notifications to all participants with phones
     *
     * @return array{sms: int, whatsapp: int, errors: int}
     */
    public function sendRemindersToAllParticipants(Appointment $appointment): array
    {
        $results = ['sms' => 0, 'whatsapp' => 0, 'errors' => 0];

        $participants = $appointment->getParticipantsWithPhones();

        foreach ($participants as $participant) {
            // Send SMS if enabled
            if ($this->isSmsEnabled()) {
                if ($this->sendSmsReminder($appointment, $participant)) {
                    $results['sms']++;
                } else {
                    $results['errors']++;
                }
            }

            // Send WhatsApp if enabled
            if ($this->isWhatsAppEnabled()) {
                if ($this->sendWhatsAppReminder($appointment, $participant)) {
                    $results['whatsapp']++;
                } else {
                    $results['errors']++;
                }
            }
        }

        return $results;
    }
}
