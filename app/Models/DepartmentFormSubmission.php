<?php

namespace App\Models;

use App\Enums\Form\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $form_id
 * @property int|null $user_id
 * @property int|null $workflow_instance_id
 * @property int|null $step_instance_id
 * @property array<array-key, mixed> $data
 * @property int $current_step
 * @property SubmissionStatus $status
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property string|null $notes
 * @property int|null $processed_by
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\DepartmentForm $form
 * @property-read \App\Models\User|null $processor
 * @property-read \App\Models\WorkflowStepInstance|null $stepInstance
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\WorkflowInstance|null $workflowInstance
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereCurrentStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereFormId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereProcessedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereStepInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentFormSubmission whereWorkflowInstanceId($value)
 * @mixin \Eloquent
 */
class DepartmentFormSubmission extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'form_id',
        'user_id',
        'workflow_instance_id',
        'step_instance_id',
        'status',
        'data',
        'current_step',
        'metadata',
        'ip_address',
        'user_agent',
        'submitted_at',
        'processed_at',
        'processed_by',
        'notes',
    ];

    protected $casts = [
        'status' => SubmissionStatus::class,
        'data' => 'array',
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'processed_at' => 'datetime',
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

    public function form(): BelongsTo
    {
        return $this->belongsTo(DepartmentForm::class, 'form_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function stepInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowStepInstance::class, 'step_instance_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isDraft(): bool
    {
        return $this->status === SubmissionStatus::DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === SubmissionStatus::SUBMITTED;
    }

    public function isProcessing(): bool
    {
        return $this->status === SubmissionStatus::PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === SubmissionStatus::COMPLETED;
    }

    public function isRejected(): bool
    {
        return $this->status === SubmissionStatus::REJECTED;
    }

    public function submit(): self
    {
        $this->update([
            'status' => SubmissionStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        return $this;
    }

    public function process(): self
    {
        $this->update(['status' => SubmissionStatus::PROCESSING]);

        return $this;
    }

    public function complete(): self
    {
        $this->update([
            'status' => SubmissionStatus::COMPLETED,
            'processed_at' => now(),
        ]);

        return $this;
    }

    public function reject(): self
    {
        $this->update([
            'status' => SubmissionStatus::REJECTED,
            'processed_at' => now(),
        ]);

        return $this;
    }

    public function updateStatus(SubmissionStatus $status, ?int $processedBy = null, ?string $notes = null): self
    {
        $data = ['status' => $status];

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        if ($processedBy !== null) {
            $data['processed_by'] = $processedBy;
        }

        // Set processed_at when completing or rejecting
        if (in_array($status, [SubmissionStatus::COMPLETED, SubmissionStatus::REJECTED])) {
            $data['processed_at'] = now();
        }

        $this->update($data);

        return $this;
    }

    public function getFieldValue(string $fieldName, $default = null)
    {
        return $this->data[$fieldName] ?? $default;
    }

    public function setFieldValue(string $fieldName, $value): self
    {
        $data = $this->data ?? [];
        $data[$fieldName] = $value;
        $this->update(['data' => $data]);

        return $this;
    }

    public function updateData(array $newData): self
    {
        $data = $this->data ?? [];
        $this->update(['data' => array_merge($data, $newData)]);

        return $this;
    }

    public function goToStep(int $step): self
    {
        $this->update(['current_step' => $step]);

        return $this;
    }

    public function nextStep(): self
    {
        $this->update(['current_step' => ($this->current_step ?? 0) + 1]);

        return $this;
    }

    public function previousStep(): self
    {
        $currentStep = $this->current_step ?? 1;
        $this->update(['current_step' => max(0, $currentStep - 1)]);

        return $this;
    }

    public function validate(): array
    {
        $errors = [];
        $form = $this->form;

        foreach ($form->fields as $field) {
            // Skip hidden fields based on conditional logic
            if (!$field->evaluateConditionalLogic($this->data ?? [])) {
                continue;
            }

            $value = $this->data[$field->name] ?? null;
            $rules = $field->getValidationRules();

            // Basic validation (in production, use Laravel's Validator)
            foreach ($rules as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $errors[$field->name][] = "The {$field->label} field is required.";
                }
            }
        }

        return $errors;
    }
}
