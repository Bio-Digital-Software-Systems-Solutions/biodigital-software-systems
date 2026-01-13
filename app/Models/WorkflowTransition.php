<?php

namespace App\Models;

use App\Enums\Workflow\TransitionConditionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

        static::creating(function (self $model) {
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
            'is_true' => (bool) $actualValue === true,
            'is_false' => (bool) $actualValue === false,
            default => false,
        };
    }
}
