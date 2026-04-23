<?php

namespace App\Models\Agile;

use App\Enums\Agile\TestScenarioExecutionStatus;
use App\Models\User;
use Database\Factories\Agile\TestScenarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $acceptance_criterion_id
 * @property string $title
 * @property string|null $given
 * @property string|null $when
 * @property string|null $then
 * @property string|null $free_form
 * @property string|null $automated_test_ref
 * @property TestScenarioExecutionStatus $execution_status
 * @property int|null $last_executed_by
 * @property \Illuminate\Support\Carbon|null $last_executed_at
 * @property string|null $failure_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read AcceptanceCriterion $acceptanceCriterion
 * @property-read User|null $lastExecutedBy
 */
class TestScenario extends Model
{
    /** @use HasFactory<TestScenarioFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'acceptance_criterion_id',
        'title',
        'given',
        'when',
        'then',
        'free_form',
        'automated_test_ref',
        'execution_status',
        'last_executed_by',
        'last_executed_at',
        'failure_notes',
    ];

    protected $casts = [
        'execution_status' => TestScenarioExecutionStatus::class,
        'last_executed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): TestScenarioFactory
    {
        return TestScenarioFactory::new();
    }

    public function acceptanceCriterion(): BelongsTo
    {
        return $this->belongsTo(AcceptanceCriterion::class);
    }

    public function lastExecutedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_executed_by');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(WorkItemComment::class, 'commentable');
    }

    public function isGherkin(): bool
    {
        return ! empty($this->given) || ! empty($this->when) || ! empty($this->then);
    }

    public function hasPassed(): bool
    {
        return $this->execution_status === TestScenarioExecutionStatus::PASSED;
    }
}
