<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
 *
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
 *
 * @mixin \Eloquent
 */
class QuizQuestion extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity;

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
     * Check if a given answer is correct.
     *
     * For multiple_choice with multiple correct answers, the student must
     * select ALL correct answers and NO wrong answers to earn the point.
     */
    public function isCorrectAnswer($answer): bool
    {
        if ($this->type === 'multiple_choice') {
            $correctAnswers = $this->correct_answers;

            if (count($correctAnswers) > 1) {
                // Multiple correct answers: answer MUST be an array with all correct values
                if (! is_array($answer) || $answer === []) {
                    return false;
                }

                $sorted = $answer;
                sort($sorted);
                $sortedCorrect = $correctAnswers;
                sort($sortedCorrect);

                return $sorted === $sortedCorrect;
            }

            // Single correct answer: accept string or single-element array
            if (is_array($answer)) {
                return count($answer) === 1 && in_array($answer[0], $correctAnswers);
            }

            return in_array($answer, $correctAnswers);
        }

        if ($this->type === 'true_false') {
            return $answer == $this->correct_answers[0];
        }

        if ($this->type === 'short_answer') {
            // Case-insensitive comparison for short answers
            /** @var string $answerStr */
            $answerStr = $answer;
            $normalizedAnswer = strtolower(trim((string) $answerStr));
            $correctAnswers = array_map(fn (string $a): string => strtolower(trim($a)), $this->correct_answers);

            return in_array($normalizedAnswer, $correctAnswers);
        }

        return false;
    }

    /**
     * Whether this question has multiple correct answers.
     */
    public function hasMultipleCorrectAnswers(): bool
    {
        return $this->type === 'multiple_choice' && count($this->correct_answers) > 1;
    }
}
