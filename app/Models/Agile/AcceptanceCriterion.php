<?php

namespace App\Models\Agile;

use App\Enums\Agile\AcceptanceCriterionStatus;
use App\Enums\Agile\TestScenarioExecutionStatus;
use App\Models\User;
use Database\Factories\Agile\AcceptanceCriterionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_story_id
 * @property int $position
 * @property string $title
 * @property string $description
 * @property AcceptanceCriterionStatus $status
 * @property int|null $validated_by
 * @property \Illuminate\Support\Carbon|null $validated_at
 * @property string|null $validation_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read UserStory $userStory
 * @property-read User|null $validatedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TestScenario> $testScenarios
 */
class AcceptanceCriterion extends Model
{
    /** @use HasFactory<AcceptanceCriterionFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'acceptance_criteria';

    protected $fillable = [
        'user_story_id',
        'position',
        'title',
        'description',
        'status',
        'validated_by',
        'validated_at',
        'validation_notes',
    ];

    protected $casts = [
        'status' => AcceptanceCriterionStatus::class,
        'position' => 'integer',
        'validated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): AcceptanceCriterionFactory
    {
        return AcceptanceCriterionFactory::new();
    }

    public function userStory(): BelongsTo
    {
        return $this->belongsTo(UserStory::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function testScenarios(): HasMany
    {
        return $this->hasMany(TestScenario::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(WorkItemComment::class, 'commentable');
    }

    public function isValidated(): bool
    {
        return $this->status === AcceptanceCriterionStatus::VALIDATED;
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function hasPassingScenarios(): bool
    {
        return $this->testScenarios()
            ->where('execution_status', TestScenarioExecutionStatus::PASSED->value)
            ->exists();
    }
}
