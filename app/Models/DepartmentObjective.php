<?php

namespace App\Models;

use App\Enums\Report\ObjectiveStatus;
use App\Enums\Priority;
use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property int|null $parent_id
 * @property int|null $assigned_to
 * @property string $title
 * @property string|null $description
 * @property ObjectiveStatus $status
 * @property Priority $priority
 * @property int $progress_percentage
 * @property \Illuminate\Support\Carbon|null $target_date
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property array<array-key, mixed>|null $key_results
 * @property array<array-key, mixed>|null $success_criteria
 * @property array<array-key, mixed>|null $blockers
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DepartmentObjective> $children
 * @property-read int|null $children_count
 * @property-read \App\Models\Department $department
 * @property-read int|null $days_remaining
 * @property-read bool $is_overdue
 * @property-read string $priority_label
 * @property-read string $status_color
 * @property-read string $status_icon
 * @property-read string $status_label
 * @property-read DepartmentObjective|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective completed()
 * @method static \Database\Factories\DepartmentObjectiveFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective forDepartment(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective forPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective rootLevel()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereBlockers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereKeyResults($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereProgressPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereSuccessCriteria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereTargetDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective withStatus(\App\Enums\Report\ObjectiveStatus $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentObjective withoutTrashed()
 * @mixin \Eloquent
 */
class DepartmentObjective extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, ClearsCache;

    protected $fillable = [
        'uuid',
        'department_id',
        'parent_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'progress_percentage',
        'target_date',
        'completed_at',
        'period_start',
        'period_end',
        'key_results',
        'success_criteria',
        'blockers',
        'metadata',
    ];

    protected $casts = [
        'status' => ObjectiveStatus::class,
        'priority' => Priority::class,
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
        'key_results' => 'array',
        'success_criteria' => 'array',
        'blockers' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'status_label',
        'status_color',
        'status_icon',
        'priority_label',
        'is_overdue',
        'days_remaining',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relations
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DepartmentObjective::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DepartmentObjective::class, 'parent_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopeForDepartment($q, int $id)
    {
        return $q->where('department_id', $id);
    }

    public function scopeForPeriod($q, Carbon $start, Carbon $end)
    {
        return $q->where('period_start', '>=', $start)->where('period_end', '<=', $end);
    }

    public function scopeWithStatus($q, ObjectiveStatus $status)
    {
        return $q->where('status', $status->value);
    }

    public function scopeActive($q)
    {
        return $q->whereIn('status', [
            ObjectiveStatus::NOT_STARTED->value,
            ObjectiveStatus::IN_PROGRESS->value,
            ObjectiveStatus::AT_RISK->value,
        ]);
    }

    public function scopeCompleted($q)
    {
        return $q->where('status', ObjectiveStatus::COMPLETED->value);
    }

    public function scopeOverdue($q)
    {
        return $q->where('target_date', '<', now())
            ->whereNotIn('status', [ObjectiveStatus::COMPLETED->value, ObjectiveStatus::CANCELLED->value]);
    }

    public function scopeRootLevel($q)
    {
        return $q->whereNull('parent_id');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    public function getStatusIconAttribute(): string
    {
        return $this->status->icon();
    }

    public function getPriorityLabelAttribute(): string
    {
        return $this->priority->label();
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->target_date) {
            return false;
        }
        return $this->target_date->isPast() &&
            !in_array($this->status, [ObjectiveStatus::COMPLETED, ObjectiveStatus::CANCELLED]);
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->target_date) {
            return null;
        }
        return (int) now()->diffInDays($this->target_date, false);
    }

    // Methods
    public function updateProgress(int $percentage): self
    {
        $this->progress_percentage = min(100, max(0, $percentage));

        if ($this->progress_percentage === 100 && $this->status !== ObjectiveStatus::COMPLETED) {
            $this->status = ObjectiveStatus::COMPLETED;
            $this->completed_at = now();
        } elseif ($this->progress_percentage > 0 && $this->status === ObjectiveStatus::NOT_STARTED) {
            $this->status = ObjectiveStatus::IN_PROGRESS;
        }

        $this->save();
        return $this;
    }

    public function markAsCompleted(): self
    {
        $this->status = ObjectiveStatus::COMPLETED;
        $this->progress_percentage = 100;
        $this->completed_at = now();
        $this->save();
        return $this;
    }

    public function addBlocker(string $description): self
    {
        $blockers = $this->blockers ?? [];
        $blockers[] = [
            'description' => $description,
            'added_at' => now()->toIso8601String(),
            'resolved' => false,
        ];
        $this->blockers = $blockers;
        $this->save();
        return $this;
    }

    public function resolveBlocker(int $index): self
    {
        $blockers = $this->blockers ?? [];
        if (isset($blockers[$index])) {
            $blockers[$index]['resolved'] = true;
            $blockers[$index]['resolved_at'] = now()->toIso8601String();
            $this->blockers = $blockers;
            $this->save();
        }
        return $this;
    }
}
