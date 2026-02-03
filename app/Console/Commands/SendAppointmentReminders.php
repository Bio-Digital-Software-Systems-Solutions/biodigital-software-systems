<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use App\Services\AppointmentSmsNotificationService;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders
                            {--hours=24 : Hours before appointment to send reminder}
                            {--dry-run : Run without actually sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for upcoming appointments (24h before by default)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hoursBeforeAppointment = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $this->info("Looking for appointments in the next {$hoursBeforeAppointment} hours...");

        // Find appointments needing reminders
        $appointments = Appointment::needingReminders($hoursBeforeAppointment)
            ->with(['organizer', 'participants'])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No appointments found requiring reminders.');

            return Command::SUCCESS;
        }

        $this->info("Found {$appointments->count()} appointment(s) requiring reminders.");

        $emailRemindersSent = 0;
        $smsRemindersSent = 0;
        $whatsappRemindersSent = 0;
        $telegramRemindersSent = 0;
        $organizerRemindersSent = 0;
        $errors = 0;

        $notificationService = app(AppointmentSmsNotificationService::class);
        $telegramService = app(TelegramNotificationService::class);

        foreach ($appointments as $appointment) {
            $this->line("Processing appointment #{$appointment->id}: {$appointment->title}...");

            if ($dryRun) {
                $this->info("  [DRY RUN] Would send reminders for appointment #{$appointment->id}");
                $this->info("    - Participants: {$appointment->participants->count()}");
                $this->info("    - Organizer: {$appointment->organizer->email}");

                continue;
            }

            try {
                // Send email reminders to confirmed participants
                $confirmedParticipants = $appointment->participants()
                    ->wherePivot('status', 'accepted')
                    ->get();

                foreach ($confirmedParticipants as $participant) {
                    try {
                        // Send email reminder
                        $participant->notify(new AppointmentReminder($appointment));
                        $emailRemindersSent++;
                        $this->info("  - Email reminder sent to {$participant->email}");

                        // Send SMS reminder if enabled and phone available
                        if ($notificationService->isSmsEnabled() && $participant->phone_number) {
                            $smsResult = $notificationService->sendSmsReminder($appointment, $participant);
                            if ($smsResult) {
                                $smsRemindersSent++;
                                $this->info("  - SMS reminder sent to {$participant->phone_number}");
                            }
                        }

                        // Send WhatsApp reminder if enabled and phone available
                        if ($notificationService->isWhatsAppEnabled() && $participant->phone_number) {
                            $whatsappResult = $notificationService->sendWhatsAppReminder($appointment, $participant);
                            if ($whatsappResult) {
                                $whatsappRemindersSent++;
                                $this->info("  - WhatsApp reminder sent to {$participant->phone_number}");
                            }
                        }

                        // Send Telegram reminder if enabled and chat_id available
                        if ($telegramService->isEnabled() && $participant->telegram_chat_id && $participant->telegram_notifications) {
                            $telegramResult = $telegramService->sendReminder($appointment, $participant);
                            if ($telegramResult) {
                                $telegramRemindersSent++;
                                $telegramIdentifier = $participant->telegram_username ?? $participant->telegram_chat_id;
                                $this->info("  - Telegram reminder sent to {$telegramIdentifier}");
                            }
                        }
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("  - Error sending reminder to {$participant->email}: {$e->getMessage()}");
                        Log::error('Failed to send appointment reminder to participant', [
                            'appointment_id' => $appointment->id,
                            'participant_id' => $participant->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Send reminder to organizer
                try {
                    $appointment->organizer->notify(new AppointmentReminder($appointment, isOrganizer: true));
                    $organizerRemindersSent++;
                    $this->info("  - Organizer email reminder sent to {$appointment->organizer->email}");

                    // Send SMS to organizer if enabled
                    if ($notificationService->isSmsEnabled() && $appointment->organizer->phone_number) {
                        $smsResult = $notificationService->sendSmsOrganizerReminder($appointment);
                        if ($smsResult) {
                            $smsRemindersSent++;
                            $this->info("  - Organizer SMS reminder sent to {$appointment->organizer->phone_number}");
                        }
                    }

                    // Send Telegram to organizer if enabled
                    if ($telegramService->isEnabled() && $appointment->organizer->telegram_chat_id && $appointment->organizer->telegram_notifications) {
                        $telegramResult = $telegramService->sendOrganizerReminder($appointment);
                        if ($telegramResult) {
                            $telegramRemindersSent++;
                            $organizerTelegramId = $appointment->organizer->telegram_username ?? $appointment->organizer->telegram_chat_id;
                            $this->info("  - Organizer Telegram reminder sent to {$organizerTelegramId}");
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  - Error sending reminder to organizer: {$e->getMessage()}");
                    Log::error('Failed to send appointment reminder to organizer', [
                        'appointment_id' => $appointment->id,
                        'organizer_id' => $appointment->organizer->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Mark appointment as reminder sent
                $appointment->markRemindersSent();

                // Update SMS/WhatsApp/Telegram tracking if any were sent
                if ($smsRemindersSent > 0) {
                    $appointment->update(['sms_reminder_sent_at' => now()]);
                }
                if ($whatsappRemindersSent > 0) {
                    $appointment->update(['whatsapp_reminder_sent_at' => now()]);
                }
                if ($telegramRemindersSent > 0) {
                    $appointment->update(['telegram_reminder_sent_at' => now()]);
                }

                Log::info('Appointment reminders sent', [
                    'appointment_id' => $appointment->id,
                    'participants_count' => $confirmedParticipants->count(),
                    'organizer_email' => $appointment->organizer->email,
                ]);
            } catch (\Exception $e) {
                $errors++;
                $this->error("  - Error processing appointment #{$appointment->id}: {$e->getMessage()}");
                Log::error('Failed to process appointment reminders', [
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
                ['Participant email reminders', $emailRemindersSent],
                ['Organizer email reminders', $organizerRemindersSent],
                ['SMS reminders', $smsRemindersSent],
                ['WhatsApp reminders', $whatsappRemindersSent],
                ['Telegram reminders', $telegramRemindersSent],
                ['Errors', $errors],
            ]
        );

        if ($errors > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
