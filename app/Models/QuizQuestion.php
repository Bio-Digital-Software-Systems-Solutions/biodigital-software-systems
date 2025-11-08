<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class QuizQuestion extends Model
{
    use HasFactory, HasUuid, LogsActivity, ClearsCache;

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'quiz_id',
        'question',
        'type',
        'options',
        'correct_answers',
        'feedback_correct',
        'feedback_incorrect',
        'points',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answers' => 'array',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Check if a given answer is correct
     */
    public function isCorrectAnswer($answer): bool
    {
        if ($this->type === 'multiple_choice') {
            // For multiple choice, answer could be a single value or array
            if (is_array($answer)) {
                sort($answer);
                $correctAnswers = $this->correct_answers;
                sort($correctAnswers);
                return $answer === $correctAnswers;
            }
            return in_array($answer, $this->correct_answers);
        }

        if ($this->type === 'true_false') {
            return $answer == $this->correct_answers[0];
        }

        if ($this->type === 'short_answer') {
            // Case-insensitive comparison for short answers
            $answer = strtolower(trim($answer));
            $correctAnswers = array_map(fn($a) => strtolower(trim($a)), $this->correct_answers);
            return in_array($answer, $correctAnswers);
        }

        return false;
    }
}
