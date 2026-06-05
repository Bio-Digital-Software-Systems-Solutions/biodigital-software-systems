<?php

namespace App\Console\Commands;

use App\Mail\CareServiceAppointmentReminder;
use App\Mail\CareServicePastorReminder;
use App\Models\CareService;
use App\Services\CareServiceNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCareServiceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'care-service:send-reminders
                            {--hours=24 : Hours before appointment to send reminder}
                            {--dry-run : Run without actually sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for upcoming care service appointments (24h before)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hoursBeforeAppointment = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        if (! config('care_service.notifications.reminders.enabled', true)) {
            $this->info('Care service reminders are disabled in configuration.');

            return Command::SUCCESS;
        }

        $this->info("Looking for appointments in the next {$hoursBeforeAppointment} hours...");

        // Calculate the time window for appointments
        $startTime = Carbon::now()->addHours($hoursBeforeAppointment - 1);
        $endTime = Carbon::now()->addHours($hoursBeforeAppointment + 1);

        // Find appointments that:
        // 1. Are confirmed or pending
        // 2. Are scheduled within the reminder window
        // 3. Haven't already received a reminder
        $appointments = CareService::whereIn('status', ['confirmed', 'pending'])
            ->whereNull('reminder_sent_at')
            ->where(function ($query) use ($startTime, $endTime): void {
                $query->whereBetween('appointment_time', [$startTime, $endTime]);
            })
            ->with(['pastor', 'user'])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No appointments found requiring reminders.');

            return Command::SUCCESS;
        }

        $this->info("Found {$appointments->count()} appointment(s) requiring reminders.");

        $clientRemindersSent = 0;
        $pastorRemindersSent = 0;
        $smsRemindersSent = 0;
        $whatsappRemindersSent = 0;
        $errors = 0;

        $notificationService = app(CareServiceNotificationService::class);

        foreach ($appointments as $appointment) {
            $this->line("Processing appointment #{$appointment->id} for {$appointment->client_name}...");

            if ($dryRun) {
                $this->info("  [DRY RUN] Would send reminders for appointment #{$appointment->id}");

                continue;
            }

            try {
                // Send client reminder (email)
                if (config('care_service.notifications.reminders.send_client_reminder', true) && $appointment->client_email) {
                    Mail::to($appointment->client_email)
                        ->send(new CareServiceAppointmentReminder($appointment));
                    $clientRemindersSent++;
                    $this->info("  - Client email reminder sent to {$appointment->client_email}");
                }

                // Send pastor reminder (email)
                if (config('care_service.notifications.reminders.send_pastor_reminder', true) && $appointment->pastor?->email) {
                    Mail::to($appointment->pastor->email)
                        ->send(new CareServicePastorReminder($appointment));
                    $pastorRemindersSent++;
                    $this->info("  - Pastor email reminder sent to {$appointment->pastor->email}");
                }

                // Send SMS reminder to client (if enabled and phone available)
                if ($notificationService->isSmsEnabled() && $appointment->client_phone) {
                    $smsResult = $notificationService->sendSmsReminder($appointment);
                    if ($smsResult) {
                        $smsRemindersSent++;
                        $this->info("  - SMS reminder sent to {$appointment->client_phone}");
                    }
                }

                // Send WhatsApp reminder to client (if enabled and phone available)
                if ($notificationService->isWhatsAppEnabled() && $appointment->client_phone) {
                    $whatsappResult = $notificationService->sendWhatsAppReminder($appointment);
                    if ($whatsappResult) {
                        $whatsappRemindersSent++;
                        $this->info("  - WhatsApp reminder sent to {$appointment->client_phone}");
                    }
                }

                // Mark appointment as reminder sent
                $appointment->update(['reminder_sent_at' => now()]);

                Log::info('Care service reminder sent', [
                    'appointment_id' => $appointment->id,
                    'client_email' => $appointment->client_email,
                    'pastor_email' => $appointment->pastor?->email,
                    'client_phone' => $appointment->client_phone,
                ]);
            } catch (\Exception $e) {
                $errors++;
                $this->error("  - Error sending reminder for appointment #{$appointment->id}: {$e->getMessage()}");
                Log::error('Failed to send care service reminder', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Type', 'Count'],
            [
                ['Client email reminders', $clientRemindersSent],
                ['Pastor email reminders', $pastorRemindersSent],
                ['SMS reminders', $smsRemindersSent],
                ['WhatsApp reminders', $whatsappRemindersSent],
                ['Errors', $errors],
            ]
        );

        if ($errors > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
