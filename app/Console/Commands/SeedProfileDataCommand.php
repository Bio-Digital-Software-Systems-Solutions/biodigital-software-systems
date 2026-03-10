<?php

namespace App\Console\Commands;

use Database\Seeders\InterestSeeder;
use Database\Seeders\ProfileSkillSeeder;
use Database\Seeders\SpokenLanguageSeeder;
use Illuminate\Console\Command;

class SeedProfileDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profile:seed-data
                            {--interests : Seed only interests}
                            {--languages : Seed only languages}
                            {--skills : Seed only skills}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed profile data: interests, languages, and skills (safe for production)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $seedInterests = $this->option('interests');
        $seedLanguages = $this->option('languages');
        $seedSkills = $this->option('skills');

        // If no specific option is provided, seed all
        $seedAll = !$seedInterests && !$seedLanguages && !$seedSkills;

        $this->info('Starting profile data seeding...');
        $this->newLine();

        if ($seedAll || $seedInterests) {
            $this->components->task('Seeding interests', function (): void {
                $this->callSilent('db:seed', ['--class' => InterestSeeder::class]);
            });
        }

        if ($seedAll || $seedLanguages) {
            $this->components->task('Seeding languages', function (): void {
                $this->callSilent('db:seed', ['--class' => SpokenLanguageSeeder::class]);
            });
        }

        if ($seedAll || $seedSkills) {
            $this->components->task('Seeding skills', function (): void {
                $this->callSilent('db:seed', ['--class' => ProfileSkillSeeder::class]);
            });
        }

        $this->newLine();
        $this->info('Profile data seeding completed!');

        return Command::SUCCESS;
    }
}
