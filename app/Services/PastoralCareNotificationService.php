<?php

namespace App\Services;

use App\Models\PastoralCare;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PastoralCareNotificationService
{
    /**
     * Check if SMS notifications are enabled
     */
    public function isSmsEnabled(): bool
    {
        return config('pastoral_care.integrations.sms.enabled', false)
            && ! empty(config('services.twilio.sid'))
            && ! empty(config('services.twilio.token'))
            && ! empty(config('services.twilio.from'));
    }

    /**
     * Check if WhatsApp notifications are enabled
     */
    public function isWhatsAppEnabled(): bool
    {
        return config('pastoral_care.integrations.whatsapp.enabled', false)
            && ! empty(config('services.twilio.sid'))
            && ! empty(config('services.twilio.token'))
            && ! empty(config('services.twilio.whatsapp_from'));
    }

    /**
     * Send SMS reminder for an appointment
     */
    public function sendSmsReminder(PastoralCare $appointment): bool
    {
        if (! $this->isSmsEnabled() || empty($appointment->client_phone)) {
            return false;
        }

        $message = $this->buildSmsReminderMessage($appointment);

        return $this->sendTwilioSms($appointment->client_phone, $message);
    }

    /**
     * Send WhatsApp reminder for an appointment
     */
    public function sendWhatsAppReminder(PastoralCare $appointment): bool
    {
        if (! $this->isWhatsAppEnabled() || empty($appointment->client_phone)) {
            return false;
        }

        $message = $this->buildWhatsAppReminderMessage($appointment);

        return $this->sendTwilioWhatsApp($appointment->client_phone, $message);
    }

    /**
     * Send SMS to pastor for reminder
     */
    public function sendSmsPastorReminder(PastoralCare $appointment): bool
    {
        if (! $this->isSmsEnabled() || ! $appointment->pastor?->phone) {
            return false;
        }

        $message = $this->buildSmsPastorReminderMessage($appointment);

        return $this->sendTwilioSms($appointment->pastor->phone, $message);
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
     * Build SMS reminder message for client
     */
    protected function buildSmsReminderMessage(PastoralCare $appointment): string
    {
        $date = $appointment->appointment_date->format('d/m/Y');
        $time = $appointment->appointment_time->format('H:i');
        $pastor = $appointment->pastor->first_name.' '.$appointment->pastor->last_name;
        $church = config('pastoral_care.church_name', 'ICC Munich');

        $locationType = match ($appointment->location_type) {
            'zoom' => 'en visioconférence',
            'hybrid' => 'en hybride',
            default => 'en présentiel',
        };

        return "Rappel {$church}: Votre RDV pastoral avec {$pastor} est demain {$date} à {$time} ({$locationType}). "
            ."En cas d'empêchement, contactez-nous. A bientôt!";
    }

    /**
     * Build WhatsApp reminder message for client (can be more detailed)
     */
    protected function buildWhatsAppReminderMessage(PastoralCare $appointment): string
    {
        $date = $appointment->appointment_date->format('d/m/Y');
        $time = $appointment->appointment_time->format('H:i');
        $pastor = $appointment->pastor->first_name.' '.$appointment->pastor->last_name;
        $church = config('pastoral_care.church_name', 'ICC Munich');
        $phone = config('pastoral_care.church_phone', '+49 89 123456789');

        $locationType = match ($appointment->location_type) {
            'zoom' => 'Visioconférence',
            'hybrid' => 'Hybride (présentiel + visio)',
            default => 'En présentiel',
        };

        $message = "Rappel de rendez-vous pastoral - {$church}\n\n";
        $message .= "Bonjour {$appointment->client_name},\n\n";
        $message .= "Votre rendez-vous de soin pastoral est prévu pour *demain*.\n\n";
        $message .= "Date: {$date}\n";
        $message .= "Heure: {$time}\n";
        $message .= "Durée: {$appointment->duration_minutes} minutes\n";
        $message .= "Pasteur: {$pastor}\n";
        $message .= "Type: {$locationType}\n";

        if ($appointment->location_type === 'zoom' && $appointment->zoom_link) {
            $message .= "\nLien Zoom: {$appointment->zoom_link}\n";
        }

        $message .= "\nEn cas d'empêchement, contactez-nous au {$phone}\n\n";

        return $message . 'Que Dieu vous bénisse!';
    }

    /**
     * Build SMS reminder message for pastor
     */
    protected function buildSmsPastorReminderMessage(PastoralCare $appointment): string
    {
        $date = $appointment->appointment_date->format('d/m');
        $time = $appointment->appointment_time->format('H:i');
        $client = $appointment->client_name;

        return "Rappel: RDV pastoral demain {$date} à {$time} avec {$client}. Consultez vos emails pour plus de détails.";
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
                Log::info('SMS sent successfully', [
                    'to' => $to,
                    'sid' => $response->json('sid'),
                ]);

                return true;
            }

            Log::error('Failed to send SMS', [
                'to' => $to,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('SMS sending exception', [
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
                Log::info('WhatsApp message sent successfully', [
                    'to' => $to,
                    'sid' => $response->json('sid'),
                ]);

                return true;
            }

            Log::error('Failed to send WhatsApp message', [
                'to' => $to,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp sending exception', [
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
        if (! str_starts_with((string) $phone, '+')) {
            // Remove leading 0 if present
            $phone = ltrim((string) $phone, '0');
            // Add German country code
            $phone = '+49'.$phone;
        }

        return $phone;
    }

    /**
     * Send confirmation notification via all channels
     */
    public function sendConfirmationNotification(PastoralCare $appointment, string $channel = 'all'): array
    {
        $results = [
            'sms' => false,
            'whatsapp' => false,
        ];

        if (empty($appointment->client_phone)) {
            return $results;
        }

        $message = $this->buildConfirmationMessage($appointment);

        if (in_array($channel, ['all', 'sms']) && $this->isSmsEnabled()) {
            $results['sms'] = $this->sendTwilioSms($appointment->client_phone, $message);
        }

        if (in_array($channel, ['all', 'whatsapp']) && $this->isWhatsAppEnabled()) {
            $whatsappMessage = $this->buildWhatsAppConfirmationMessage($appointment);
            $results['whatsapp'] = $this->sendTwilioWhatsApp($appointment->client_phone, $whatsappMessage);
        }

        return $results;
    }

    /**
     * Build confirmation message for SMS
     */
    protected function buildConfirmationMessage(PastoralCare $appointment): string
    {
        $date = $appointment->appointment_date->format('d/m/Y');
        $time = $appointment->appointment_time->format('H:i');
        $church = config('pastoral_care.church_name', 'ICC Munich');

        return "{$church}: Votre RDV pastoral du {$date} à {$time} est confirmé. Merci!";
    }

    /**
     * Build confirmation message for WhatsApp
     */
    protected function buildWhatsAppConfirmationMessage(PastoralCare $appointment): string
    {
        $date = $appointment->appointment_date->format('d/m/Y');
        $time = $appointment->appointment_time->format('H:i');
        $pastor = $appointment->pastor->first_name.' '.$appointment->pastor->last_name;
        $church = config('pastoral_care.church_name', 'ICC Munich');

        $message = "Confirmation de rendez-vous - {$church}\n\n";
        $message .= "Votre rendez-vous de soin pastoral est confirmé !\n\n";
        $message .= "Date: {$date}\n";
        $message .= "Heure: {$time}\n";
        $message .= "Pasteur: {$pastor}\n\n";
        $message .= "Vous recevrez un rappel 24h avant le rendez-vous.\n\n";

        return $message . 'Que Dieu vous bénisse !';
    }

    /**
     * Send cancellation notification via all channels
     */
    public function sendCancellationNotification(PastoralCare $appointment, string $channel = 'all'): array
    {
        $results = [
            'sms' => false,
            'whatsapp' => false,
        ];

        if (empty($appointment->client_phone)) {
            return $results;
        }

        $message = $this->buildCancellationMessage($appointment);

        if (in_array($channel, ['all', 'sms']) && $this->isSmsEnabled()) {
            $results['sms'] = $this->sendTwilioSms($appointment->client_phone, $message);
        }

        if (in_array($channel, ['all', 'whatsapp']) && $this->isWhatsAppEnabled()) {
            $results['whatsapp'] = $this->sendTwilioWhatsApp($appointment->client_phone, $message);
        }

        return $results;
    }

    /**
     * Build cancellation message
     */
    protected function buildCancellationMessage(PastoralCare $appointment): string
    {
        $date = $appointment->appointment_date->format('d/m/Y');
        $time = $appointment->appointment_time->format('H:i');
        $church = config('pastoral_care.church_name', 'ICC Munich');
        $phone = config('pastoral_care.church_phone', '+49 89 123456789');

        return "{$church}: Votre RDV du {$date} à {$time} a été annulé. Pour reprendre RDV: {$phone}";
    }
}
