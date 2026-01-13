<?php

namespace App\Models;

use App\Enums\Form\FormStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DepartmentForm extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'department_id',
        'created_by',
        'name',
        'description',
        'status',
        'is_multi_step',
        'settings',
        'validation_rules',
        'conditional_logic',
        'success_message',
        'redirect_url',
        'is_template',
        'parent_form_id',
        'version',
        'published_at',
    ];

    protected $casts = [
        'status' => FormStatus::class,
        'is_multi_step' => 'boolean',
        'is_template' => 'boolean',
        'settings' => 'array',
        'validation_rules' => 'array',
        'conditional_logic' => 'array',
        'published_at' => 'datetime',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentForm(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_form_id');
    }

    public function childForms(): HasMany
    {
        return $this->hasMany(self::class, 'parent_form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_id')->orderBy('order');
    }

    public function rootFields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_id')
            ->whereNull('parent_field_id')
            ->orderBy('order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(DepartmentFormSubmission::class, 'form_id');
    }

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'form_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isDraft(): bool
    {
        return $this->status === FormStatus::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === FormStatus::PUBLISHED;
    }

    public function isArchived(): bool
    {
        return $this->status === FormStatus::ARCHIVED;
    }

    public function publish(): self
    {
        $this->update([
            'status' => FormStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        return $this;
    }

    public function archive(): self
    {
        $this->update(['status' => FormStatus::ARCHIVED]);

        return $this;
    }

    public function unpublish(): self
    {
        $this->update([
            'status' => FormStatus::DRAFT,
            'published_at' => null,
        ]);

        return $this;
    }

    public function duplicate(): self
    {
        $clone = $this->replicate(['uuid', 'published_at']);
        $clone->uuid = (string) Str::uuid();
        $clone->status = FormStatus::DRAFT;
        $clone->name = $this->name . ' (Copy)';
        $clone->version = 1;
        $clone->parent_form_id = $this->id;
        $clone->save();

        // Duplicate fields
        $this->duplicateFields($this->rootFields, $clone->id, null);

        return $clone;
    }

    protected function duplicateFields($fields, int $formId, ?int $parentFieldId): void
    {
        foreach ($fields as $field) {
            $newField = $field->replicate(['uuid']);
            $newField->uuid = (string) Str::uuid();
            $newField->form_id = $formId;
            $newField->parent_field_id = $parentFieldId;
            $newField->save();

            // Recursively duplicate child fields
            if ($field->children->isNotEmpty()) {
                $this->duplicateFields($field->children, $formId, $newField->id);
            }
        }
    }

    public function getFieldsStructure(): array
    {
        return $this->rootFields->map(function ($field) {
            return $this->buildFieldStructure($field);
        })->toArray();
    }

    protected function buildFieldStructure(FormField $field): array
    {
        $structure = $field->toArray();
        $structure['children'] = $field->children->map(function ($child) {
            return $this->buildFieldStructure($child);
        })->toArray();

        return $structure;
    }

    public function getSubmissionCount(): int
    {
        return $this->submissions()->count();
    }

    public function getCompletedSubmissionCount(): int
    {
        return $this->submissions()->where('status', 'completed')->count();
    }
}
