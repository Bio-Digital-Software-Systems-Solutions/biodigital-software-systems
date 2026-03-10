<?php

namespace App\Console\Commands;

use App\Enums\Event\ParticipantRole;
use App\Enums\Event\RegistrationStatus;
use App\Models\Event;
use App\Models\Event\EventRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncParticipantsToRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:sync-participants
                            {--event= : Sync only a specific event by ID or UUID}
                            {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize legacy event participants to the new registration system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $eventOption = $this->option('event');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('🔄 Synchronizing participants to registrations...');
        $this->newLine();

        // Get events to process
        $query = Event::with(['participants']);

        if ($eventOption) {
            $query->where('id', $eventOption)
                  ->orWhere('uuid', $eventOption);
        }

        $events = $query->get();

        if ($events->isEmpty()) {
            $this->warn('No events found to process.');
            return self::SUCCESS;
        }

        $totalCreated = 0;
        $totalSkipped = 0;
        $totalEvents = $events->count();

        $this->info("Processing {$totalEvents} event(s)...");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalEvents);
        $progressBar->start();

        foreach ($events as $event) {
            $participants = $event->participants;

            foreach ($participants as $participant) {
                // Check if registration already exists
                $existingRegistration = EventRegistration::where('event_id', $event->id)
                    ->where('user_id', $participant->id)
                    ->whereNotIn('status', [RegistrationStatus::CANCELLED])
                    ->first();

                if ($existingRegistration) {
                    $totalSkipped++;
                    continue;
                }

                if (!$isDryRun) {
                    DB::transaction(function () use ($event, $participant): void {
                        EventRegistration::create([
                            'event_id' => $event->id,
                            'user_id' => $participant->id,
                            'first_name' => $participant->first_name ?? $participant->name,
                            'last_name' => $participant->last_name ?? '',
                            'email' => $participant->email,
                            'phone' => $participant->phone,
                            'status' => RegistrationStatus::CONFIRMED,
                            'participant_role' => ParticipantRole::ATTENDEE,
                            'quantity' => 1,
                            'unit_price' => 0,
                            'discount_amount' => 0,
                            'total_amount' => 0,
                            'currency' => 'EUR',
                            'registered_at' => $participant->pivot->registered_at ?? $participant->pivot->created_at ?? now(),
                            'confirmed_at' => $participant->pivot->registered_at ?? $participant->pivot->created_at ?? now(),
                        ]);
                    });
                }

                $totalCreated++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📊 Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Events Processed', $totalEvents],
                ['Registrations Created', $totalCreated],
                ['Already Existed (Skipped)', $totalSkipped],
            ]
        );

        if ($isDryRun) {
            $this->newLine();
            $this->info('ℹ️  Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('✅ Synchronization completed successfully!');
        }

        return self::SUCCESS;
    }
}
