<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Backup\Tasks\Backup\BackupJob;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:database
                          {--only-db : Only backup the database}
                          {--only-files : Only backup the files}';

    /**
     * The console command description.
     */
    protected $description = 'Backup database and files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting backup process...');

        try {
            $backupJob = BackupJobFactory::createFromArray(config('backup'));

            if ($this->option('only-db')) {
                $backupJob->dontBackupFilesystem();
                $this->info('Backing up database only...');
            }

            if ($this->option('only-files')) {
                $backupJob->dontBackupDatabases();
                $this->info('Backing up files only...');
            }

            $backupJob->run();

            $this->info('Backup completed successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
