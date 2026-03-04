<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Here you may define all of your scheduled tasks. Laravel's scheduler
| allows you to fluently express your command schedule using a clean
| syntax that is easy to understand.
|
*/

// Process queued jobs every minute (for shared hosting without Supervisor)
// The worker starts, processes all pending jobs, then exits
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue-worker.log'))
    ->description('Process queued jobs (shared hosting fallback)');

// Send appointment reminders daily at 9:00 AM
// This will notify participants and organizers 24 hours before their appointments
Schedule::command('appointments:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/appointment-reminders.log'))
    ->description('Send appointment reminder notifications (24h before)');

// Optional: Send a second reminder at 6:00 PM for next-day appointments
Schedule::command('appointments:send-reminders --hours=18')
    ->dailyAt('18:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/appointment-reminders.log'))
    ->description('Send appointment reminder notifications (18h before)');
