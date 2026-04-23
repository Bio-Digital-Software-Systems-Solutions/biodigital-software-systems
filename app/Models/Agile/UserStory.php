<?php

namespace App\Models\Agile;

use App\Enums\Agile\UserStoryStatus;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Traits\HasUuid;
use Database\Factories\Agile\UserStoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $epic_id
 * @property int|null $sprint_id
 * @property int|null $assignee_id
 * @property int $reporter_id
 * @property string $title
 * @property string $as_a
 * @property string $i_want
 * @property string $so_that
 * @property int|null $story_points
 * @property int $priority
 * @property UserStoryStatus $status
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Epic|null $epic
 * @property-read Sprint|null $sprint
 * @property-read User|null $assignee
 * @property-read User $reporter
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AcceptanceCriterion> $acceptanceCriteria
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $storyTasks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TestScenario> $testScenarios
 */
class UserStory extends Model
{
    /** @use HasFactory<UserStoryFactory> */
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'epic_id',
        'sprint_id',
        'assignee_id',
        'reporter_id',
        'title',
        'as_a',
        'i_want',
        'so_that',
        'story_points',
        'priority',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'status' => UserStoryStatus::class,
        'story_points' => 'integer',
        'priority' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): UserStoryFactory
    {
        return UserStoryFactory::new();
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function acceptanceCriteria(): HasMany
    {
        return $this->hasMany(AcceptanceCriterion::class)->orderBy('position');
    }

    /**
     * Story-level technical tasks stored in the legacy tasks table via polymorphism.
     */
    public function storyTasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function testScenarios(): HasManyThrough
    {
        return $this->hasManyThrough(
            TestScenario::class,
            AcceptanceCriterion::class,
            'user_story_id',
            'acceptance_criterion_id'
        );
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(WorkItemComment::class, 'commentable');
    }

    public function outgoingLinks(): MorphMany
    {
        return $this->morphMany(WorkItemLink::class, 'source');
    }

    public function incomingLinks(): MorphMany
    {
        return $this->morphMany(WorkItemLink::class, 'target');
    }

    /**
     * True when every acceptance criterion is validated.
     *
     * An empty story (no criteria) returns false — a story without explicit
     * acceptance criteria cannot be signed off.
     */
    public function canBeCompleted(): bool
    {
        $criteria = $this->acceptanceCriteria;

        if ($criteria->isEmpty()) {
            return false;
        }

        return $criteria->every(fn (AcceptanceCriterion $ac): bool => $ac->isValidated());
    }

    public function pendingCriteriaCount(): int
    {
        return $this->acceptanceCriteria
            ->reject(fn (AcceptanceCriterion $ac): bool => $ac->isValidated())
            ->count();
    }
}
