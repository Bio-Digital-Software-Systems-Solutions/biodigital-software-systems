<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use App\Models\User;
use App\Notifications\QuizDeadlineReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendQuizDeadlineReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quiz:send-deadline-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send deadline reminder notifications for quizzes expiring in 3 days or 1 day';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for quizzes with upcoming deadlines...');

        $now = Carbon::now();
        $threeDaysFromNow = $now->copy()->addDays(3);
        $oneDayFromNow = $now->copy()->addDays(1);

        // Find published quizzes with deadlines in 3 days or 1 day
        $quizzes = Quiz::with(['training.users'])
            ->where('status', 'published')
            ->whereNotNull('available_until')
            ->where('available_until', '>', $now)
            ->where(function ($query) use ($threeDaysFromNow, $oneDayFromNow): void {
                // Deadline is in approximately 3 days (within 1 hour window)
                $query->whereBetween('available_until', [
                    $threeDaysFromNow->copy()->subHour(),
                    $threeDaysFromNow->copy()->addHour()
                ])
                // OR deadline is in approximately 1 day (within 1 hour window)
                ->orWhereBetween('available_until', [
                    $oneDayFromNow->copy()->subHour(),
                    $oneDayFromNow->copy()->addHour()
                ]);
            })
            ->get();

        $this->info("Found {$quizzes->count()} quiz(zes) with upcoming deadlines");

        $notificationsSent = 0;

        foreach ($quizzes as $quiz) {
            $daysRemaining = $now->diffInDays($quiz->available_until);

            // Round to nearest day (1 or 3)
            $daysRemaining = $daysRemaining <= 1 ? 1 : 3;

            $this->info("Processing quiz: {$quiz->title} (Deadline: {$quiz->available_until->format('Y-m-d H:i')}, Days remaining: {$daysRemaining})");

            // Get all enrolled students for this training
            $enrolledStudents = $quiz->training->users()
                ->whereHas('roles', function ($query): void {
                    $query->whereIn('name', ['member', 'student']);
                })
                ->get();

            foreach ($enrolledStudents as $student) {
                // Check if student has already completed the quiz
                $hasCompleted = $quiz->attempts()
                    ->where('user_id', $student->id)
                    ->where('completed_at', '!=', null)
                    ->exists();

                if (!$hasCompleted) {
                    // Check if we already sent this reminder today
                    $alreadySentToday = $student->notifications()
                        ->where('type', QuizDeadlineReminder::class)
                        ->where('created_at', '>=', $now->startOfDay())
                        ->whereJsonContains('data->quiz_id', $quiz->id)
                        ->whereJsonContains('data->days_remaining', $daysRemaining)
                        ->exists();

                    if (!$alreadySentToday) {
                        $student->notify(new QuizDeadlineReminder($quiz, $daysRemaining));
                        $notificationsSent++;
                        $this->line("  → Sent reminder to {$student->first_name} {$student->last_name}");
                    } else {
                        $this->line("  → Already sent reminder today to {$student->first_name} {$student->last_name}");
                    }
                }
            }
        }

        $this->info("✓ Sent {$notificationsSent} deadline reminder notification(s)");

        return Command::SUCCESS;
    }
}
