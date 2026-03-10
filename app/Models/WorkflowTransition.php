<?php

namespace App\Models;

use App\Enums\Workflow\TransitionConditionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $workflow_id
 * @property int $from_step_id
 * @property int $to_step_id
 * @property string|null $name
 * @property TransitionConditionType $condition_type
 * @property string|null $condition_expression
 * @property array<array-key, mixed>|null $condition_config
 * @property int $priority
 * @property bool $is_default
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $label
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\WorkflowStep $fromStep
 * @property-read \App\Models\WorkflowStep $toStep
 * @property-read \App\Models\DepartmentWorkflow $workflow
 * @method static \Database\Factories\WorkflowTransitionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereConditionConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereConditionExpression($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereConditionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereFromStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereToStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkflowTransition whereWorkflowId($value)
 * @mixin \Eloquent
 */
class WorkflowTransition extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'workflow_id',
        'from_step_id',
        'to_step_id',
        'name',
        'condition_type',
        'condition_config',
        'priority',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'condition_type' => TransitionConditionType::class,
        'condition_config' => 'array',
        'metadata' => 'array',
        'is_default' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(DepartmentWorkflow::class, 'workflow_id');
    }

    public function fromStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'from_step_id');
    }

    public function toStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'to_step_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function isConditional(): bool
    {
        return $this->condition_type !== TransitionConditionType::ALWAYS;
    }

    /**
     * Evaluate the transition condition against the given context.
     */
    public function evaluate(array $context): bool
    {
        if ($this->condition_type === TransitionConditionType::ALWAYS) {
            return true;
        }

        $config = $this->condition_config ?? [];

        return match ($this->condition_type) {
            TransitionConditionType::EXPRESSION => $this->evaluateExpression($config, $context),
            TransitionConditionType::APPROVAL_RESULT => $this->evaluateApprovalResult($config, $context),
            TransitionConditionType::FORM_FIELD => $this->evaluateFormField($config, $context),
            TransitionConditionType::VARIABLE => $this->evaluateVariable($config, $context),
            default => false,
        };
    }

    protected function evaluateExpression(array $config, array $context): bool
    {
        // Simple expression evaluation - can be extended with a proper expression parser
        $expression = $config['expression'] ?? '';

        // Replace variables in expression with context values
        foreach ($context as $key => $value) {
            $expression = str_replace('{{' . $key . '}}', json_encode($value), $expression);
        }

        // For now, return true if expression is non-empty
        // In production, use a proper expression evaluator like symfony/expression-language
        return !empty($expression);
    }

    protected function evaluateApprovalResult(array $config, array $context): bool
    {
        $expectedResult = $config['result'] ?? 'approved';
        $actualResult = $context['approval_result'] ?? null;

        return $actualResult === $expectedResult;
    }

    protected function evaluateFormField(array $config, array $context): bool
    {
        $fieldName = $config['field'] ?? '';
        $operator = $config['operator'] ?? 'equals';
        $expectedValue = $config['value'] ?? null;

        $actualValue = $context['form_data'][$fieldName] ?? null;

        return match ($operator) {
            'equals' => $actualValue == $expectedValue,
            'not_equals' => $actualValue != $expectedValue,
            'greater_than' => $actualValue > $expectedValue,
            'less_than' => $actualValue < $expectedValue,
            'contains' => str_contains((string) $actualValue, (string) $expectedValue),
            'is_empty' => empty($actualValue),
            'is_not_empty' => !empty($actualValue),
            default => false,
        };
    }

    protected function evaluateVariable(array $config, array $context): bool
    {
        $variableName = $config['variable'] ?? '';
        $operator = $config['operator'] ?? 'equals';
        $expectedValue = $config['value'] ?? null;

        $actualValue = $context[$variableName] ?? null;

        return match ($operator) {
            'equals' => $actualValue == $expectedValue,
            'not_equals' => $actualValue != $expectedValue,
            'is_true' => (bool) $actualValue,
            'is_false' => (bool) $actualValue === false,
            default => false,
        };
    }
}
