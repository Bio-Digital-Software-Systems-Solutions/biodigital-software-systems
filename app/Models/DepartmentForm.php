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

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property int $created_by
 * @property string $name
 * @property string|null $description
 * @property FormStatus $status
 * @property string $scope
 * @property bool $is_template
 * @property int|null $parent_form_id
 * @property bool $is_multi_step
 * @property array<array-key, mixed>|null $settings
 * @property array<array-key, mixed>|null $validation_rules
 * @property array<array-key, mixed>|null $conditional_logic
 * @property string|null $success_message
 * @property string|null $redirect_url
 * @property string|null $submit_action
 * @property string|null $submit_config
 * @property int $version
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $archived_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DepartmentForm> $childForms
 * @property-read int|null $child_forms_count
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FormField> $fields
 * @property-read int|null $fields_count
 * @property-read DepartmentForm|null $parentForm
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FormField> $rootFields
 * @property-read int|null $root_fields_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FormShareLink> $shareLinks
 * @property-read int|null $share_links_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentFormSubmission> $submissions
 * @property-read int|null $submissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowStep> $workflowSteps
 * @property-read int|null $workflow_steps_count
 * @method static \Database\Factories\DepartmentFormFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereConditionalLogic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereIsMultiStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereIsTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereParentFormId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereSubmitAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereSubmitConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereSuccessMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereValidationRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentForm withoutTrashed()
 * @mixin \Eloquent
 */
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

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // Cascade delete share links when form is deleted (soft or force)
        static::deleting(function (self $model): void {
            $model->shareLinks()->delete();
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

    public function shareLinks(): HasMany
    {
        return $this->hasMany(FormShareLink::class, 'form_id');
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
        return $this->rootFields->map($this->buildFieldStructure(...))->toArray();
    }

    protected function buildFieldStructure(FormField $field): array
    {
        $structure = $field->toArray();
        $structure['children'] = $field->children->map($this->buildFieldStructure(...))->toArray();

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
