<?php

namespace App\Observers;

use App\Models\Quiz;
use App\Notifications\QuizPublishedNotification;

class QuizObserver
{
    /**
     * Handle the Quiz "created" event.
     */
    public function created(Quiz $quiz): void
    {
        // If quiz is created with published status, send notifications
        if ($quiz->status === 'published') {
            $this->notifyStudentsOfPublishedQuiz($quiz);
        }
    }

    /**
     * Handle the Quiz "updated" event.
     */
    public function updated(Quiz $quiz): void
    {
        // Check if status changed to published
        if ($quiz->wasChanged('status') && $quiz->status === 'published') {
            $this->notifyStudentsOfPublishedQuiz($quiz);
        }
    }

    /**
     * Handle the Quiz "deleted" event.
     */
    public function deleted(Quiz $quiz): void
    {
        //
    }

    /**
     * Handle the Quiz "restored" event.
     */
    public function restored(Quiz $quiz): void
    {
        //
    }

    /**
     * Handle the Quiz "force deleted" event.
     */
    public function forceDeleted(Quiz $quiz): void
    {
        //
    }

    /**
     * Send notifications to enrolled students when quiz is published
     */
    private function notifyStudentsOfPublishedQuiz(Quiz $quiz): void
    {
        // Load the training relationship if not already loaded
        $quiz->load('training.students');

        // Get all enrolled students for this training
        $enrolledStudents = $quiz->training->students()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['member', 'student']);
            })
            ->wherePivot('status', 'approved')  // Only notify approved students
            ->get();

        // Send notification to each student
        foreach ($enrolledStudents as $student) {
            $student->notify(new QuizPublishedNotification($quiz));
        }
    }
}
