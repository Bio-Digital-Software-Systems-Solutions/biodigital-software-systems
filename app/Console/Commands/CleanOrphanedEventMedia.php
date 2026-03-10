<?php

namespace App\Console\Commands;

use App\Models\Event\EventMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOrphanedEventMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:clean-orphaned-media {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove event media records where the file no longer exists';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Scanning for orphaned event media records...');

        $media = EventMedia::all();
        $orphaned = collect();

        $bar = $this->output->createProgressBar($media->count());
        $bar->start();

        foreach ($media as $item) {
            if (! Storage::disk('public')->exists($item->file_path)) {
                $orphaned->push($item);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($orphaned->isEmpty()) {
            $this->info('No orphaned media records found.');

            return self::SUCCESS;
        }

        $this->warn("Found {$orphaned->count()} orphaned media records:");

        $this->table(
            ['ID', 'Event ID', 'File Name', 'Type', 'File Path'],
            $orphaned->map(fn ($m): array => [
                $m->id,
                $m->event_id,
                $m->file_name,
                $m->media_type,
                $m->file_path,
            ])
        );

        if ($dryRun) {
            $this->info('Dry run - no records were deleted.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Do you want to delete these orphaned records?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($orphaned as $item) {
            $item->forceDelete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} orphaned media records.");

        return self::SUCCESS;
    }
}
