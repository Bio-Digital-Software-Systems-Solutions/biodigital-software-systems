<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $quiz_id
 * @property string $question
 * @property string $type
 * @property array<array-key, mixed>|null $options
 * @property array<array-key, mixed> $correct_answers
 * @property string|null $feedback_correct
 * @property string|null $feedback_incorrect
 * @property int $points
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Quiz $quiz
 * @method static \Database\Factories\QuizQuestionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereCorrectAnswers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereFeedbackCorrect($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereFeedbackIncorrect($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion wherePoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereQuizId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereUuid($value)
 * @mixin \Eloquent
 */
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
            $answer = strtolower(trim((string) $answer));
            $correctAnswers = array_map(fn($a) => strtolower(trim((string) $a)), $this->correct_answers);
            return in_array($answer, $correctAnswers);
        }

        return false;
    }
}
