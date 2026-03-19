<?php

namespace App\Models;

use App\Enums\RoutineFrequency;
use App\Enums\RoutineStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Routine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'department_id',
        'name',
        'description',
        'status',
        'frequency',
        'responsible_id',
        'created_by',
        'approved_by',
        'approved_at',
        'activated_at',
        'estimated_duration_minutes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'status' => RoutineStatus::class,
        'frequency' => RoutineFrequency::class,
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
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

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RoutineStep::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    public function allSteps(): HasMany
    {
        return $this->hasMany(RoutineStep::class)->orderBy('sort_order');
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(RoutineAssignee::class)->whereNull('routine_step_id');
    }

    public function allAssignees(): HasMany
    {
        return $this->hasMany(RoutineAssignee::class);
    }

    public function sops(): HasMany
    {
        return $this->hasMany(RoutineSop::class)->whereNull('routine_step_id')->orderBy('sort_order');
    }

    public function allSops(): HasMany
    {
        return $this->hasMany(RoutineSop::class)->orderBy('sort_order');
    }

    // Scopes

    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByStatus(Builder $query, RoutineStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // Status transitions

    public function submitForApproval(): bool
    {
        if (! $this->status->canTransitionTo(RoutineStatus::PendingApproval)) {
            return false;
        }

        $this->update(['status' => RoutineStatus::PendingApproval]);

        return true;
    }

    public function approve(User $approver): bool
    {
        if (! $this->status->canTransitionTo(RoutineStatus::Approved)) {
            return false;
        }

        $this->update([
            'status' => RoutineStatus::Approved,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function reject(): bool
    {
        if (! $this->status->canTransitionTo(RoutineStatus::Draft)) {
            return false;
        }

        $this->update([
            'status' => RoutineStatus::Draft,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return true;
    }

    public function activate(): bool
    {
        if (! $this->status->canTransitionTo(RoutineStatus::Active)) {
            return false;
        }

        $this->update([
            'status' => RoutineStatus::Active,
            'is_active' => true,
            'activated_at' => now(),
        ]);

        return true;
    }

    public function archive(): bool
    {
        if (! $this->status->canTransitionTo(RoutineStatus::Archived)) {
            return false;
        }

        $this->update([
            'status' => RoutineStatus::Archived,
            'is_active' => false,
        ]);

        return true;
    }

    // Accessors

    public function getIsEditableAttribute(): bool
    {
        return $this->status === RoutineStatus::Draft;
    }

    public function getTotalEstimatedDurationAttribute(): int
    {
        return $this->allSteps()->sum('duration_minutes') ?? 0;
    }
}
