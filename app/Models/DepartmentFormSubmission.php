<?php

namespace App\Models;

use App\Enums\Form\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
