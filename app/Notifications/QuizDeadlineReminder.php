<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizDeadlineReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Quiz $quiz,
        public int $daysRemaining
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->daysRemaining === 1
            ? "⚠️ Dernier jour pour compléter le quiz: {$this->quiz->title}"
            : "📝 Rappel: Quiz à compléter dans {$this->daysRemaining} jours";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Ce message est un rappel concernant le quiz **{$this->quiz->title}**.")
            ->line("📚 Formation: {$this->quiz->training->title}")
            ->line("⏰ Date limite: " . $this->quiz->available_until->format('d/m/Y à H:i'))
            ->line("⏱️ Durée: {$this->quiz->duration_minutes} minutes")
            ->line("🎯 Score minimum requis: {$this->quiz->passing_score}%")
            ->when($this->daysRemaining === 1, fn($message) => $message->line("⚠️ **Il ne vous reste plus qu'un jour pour compléter ce quiz!**"))
            ->action('Commencer le quiz', route('quizzes.start', $this->quiz->uuid))
            ->line('Bonne chance!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'quiz_id' => $this->quiz->id,
            'quiz_uuid' => $this->quiz->uuid,
            'quiz_title' => $this->quiz->title,
            'training_title' => $this->quiz->training->title,
            'days_remaining' => $this->daysRemaining,
            'deadline' => $this->quiz->available_until?->toISOString(),
            'type' => 'quiz_deadline_reminder',
        ];
    }
}
