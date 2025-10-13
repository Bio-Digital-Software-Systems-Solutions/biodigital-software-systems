<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Quiz $quiz
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
        $message = (new MailMessage)
            ->subject("🆕 Nouveau quiz disponible: {$this->quiz->title}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Un nouveau quiz vient d'être publié pour votre formation!")
            ->line("📝 **{$this->quiz->title}**")
            ->line("📚 Formation: {$this->quiz->training->title}");

        if ($this->quiz->description) {
            $message->line("ℹ️ {$this->quiz->description}");
        }

        $message->line("⏱️ Durée: {$this->quiz->duration_minutes} minutes")
            ->line("🎯 Score minimum requis: {$this->quiz->passing_score}%")
            ->line("🔢 Tentatives autorisées: {$this->quiz->max_attempts}");

        if ($this->quiz->available_from && $this->quiz->available_from->isFuture()) {
            $message->line("📅 Disponible dès le: " . $this->quiz->available_from->format('d/m/Y à H:i'));
        }

        if ($this->quiz->available_until) {
            $message->line("⏰ Date limite: " . $this->quiz->available_until->format('d/m/Y à H:i'));
        }

        $message->action('Voir le quiz', route('quizzes.start', $this->quiz->uuid))
            ->line('Bonne chance!');

        return $message;
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
            'deadline' => $this->quiz->available_until?->toISOString(),
            'type' => 'quiz_published',
        ];
    }
}
