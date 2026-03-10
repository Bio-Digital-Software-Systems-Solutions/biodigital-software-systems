<?php

namespace App\Models\Scheduling;

use App\Enums\Scheduling\ScheduleStatus;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property \Illuminate\Support\Carbon $week_start
 * @property \Illuminate\Support\Carbon $week_end
 * @property ScheduleStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $published_by
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $createdBy
 * @property-read Department $department
 * @property-read bool $is_current_week
 * @property-read bool $is_editable
 * @property-read bool $is_locked
 * @property-read bool $is_past
 * @property-read string $week_label
 * @property-read int $week_number
 * @property-read User|null $publishedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scheduling\Shift> $shifts
 * @property-read int|null $shifts_count
 * @method static Builder<static>|WeeklySchedule current()
 * @method static \Database\Factories\Scheduling\WeeklyScheduleFactory factory($count = null, $state = [])
 * @method static Builder<static>|WeeklySchedule forDepartment(int $departmentId)
 * @method static Builder<static>|WeeklySchedule forWeek(\Carbon\Carbon $date)
 * @method static Builder<static>|WeeklySchedule newModelQuery()
 * @method static Builder<static>|WeeklySchedule newQuery()
 * @method static Builder<static>|WeeklySchedule published()
 * @method static Builder<static>|WeeklySchedule query()
 * @method static Builder<static>|WeeklySchedule upcoming()
 * @method static Builder<static>|WeeklySchedule whereCreatedAt($value)
 * @method static Builder<static>|WeeklySchedule whereCreatedBy($value)
 * @method static Builder<static>|WeeklySchedule whereDepartmentId($value)
 * @method static Builder<static>|WeeklySchedule whereId($value)
 * @method static Builder<static>|WeeklySchedule whereLockedAt($value)
 * @method static Builder<static>|WeeklySchedule whereNotes($value)
 * @method static Builder<static>|WeeklySchedule wherePublishedAt($value)
 * @method static Builder<static>|WeeklySchedule wherePublishedBy($value)
 * @method static Builder<static>|WeeklySchedule whereStatus($value)
 * @method static Builder<static>|WeeklySchedule whereUpdatedAt($value)
 * @method static Builder<static>|WeeklySchedule whereUuid($value)
 * @method static Builder<static>|WeeklySchedule whereWeekEnd($value)
 * @method static Builder<static>|WeeklySchedule whereWeekStart($value)
 * @mixin \Eloquent
 */
class WeeklySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'department_id',
        'week_start',
        'week_end',
        'status',
        'notes',
        'created_by',
        'published_by',
        'published_at',
        'locked_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'status' => ScheduleStatus::class,
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
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

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    // Scopes
    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeForWeek(Builder $query, Carbon $date): Builder
    {
        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);

        return $query->where('week_start', $weekStart);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ScheduleStatus::PUBLISHED);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('week_start', '<=', $now)
            ->where('week_end', '>=', $now);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('week_start', '>', Carbon::now());
    }

    // Accessors
    public function getWeekLabelAttribute(): string
    {
        return 'Semaine '.$this->week_start->isoWeek().' ('.
            $this->week_start->format('d/m').' - '.
            $this->week_end->format('d/m/Y').')';
    }

    public function getWeekNumberAttribute(): int
    {
        return (int) $this->week_start->isoWeek();
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->status === ScheduleStatus::DRAFT;
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->status === ScheduleStatus::LOCKED;
    }

    public function getIsCurrentWeekAttribute(): bool
    {
        $now = Carbon::now();

        return $this->week_start <= $now && $this->week_end >= $now;
    }

    public function getIsPastAttribute(): bool
    {
        return $this->week_end < Carbon::now();
    }

    // Methods
    public function publish(User $user): bool
    {
        if (! $this->status->canTransitionTo(ScheduleStatus::PUBLISHED)) {
            return false;
        }

        $this->update([
            'status' => ScheduleStatus::PUBLISHED,
            'published_by' => $user->id,
            'published_at' => now(),
        ]);

        return true;
    }

    public function lock(): bool
    {
        if (! $this->status->canTransitionTo(ScheduleStatus::LOCKED)) {
            return false;
        }

        $this->update([
            'status' => ScheduleStatus::LOCKED,
            'locked_at' => now(),
        ]);

        return true;
    }

    public function unpublish(): bool
    {
        if ($this->status !== ScheduleStatus::PUBLISHED) {
            return false;
        }

        $this->update([
            'status' => ScheduleStatus::DRAFT,
            'published_by' => null,
            'published_at' => null,
        ]);

        return true;
    }

    public function getShiftsForDay(Carbon $date): Collection
    {
        return $this->shifts()
            ->whereDate('date', $date)
            ->orderBy('start_time')
            ->get();
    }

    public function getShiftsByUser(): Collection
    {
        return $this->shifts()
            ->whereNotNull('assigned_to')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('assigned_to');
    }

    public function getStaffingByDay(): array
    {
        $staffing = [];
        $current = $this->week_start->copy();

        while ($current <= $this->week_end) {
            $dayShifts = $this->shifts()->whereDate('date', $current)->get();
            $staffing[$current->format('Y-m-d')] = [
                'date' => $current->copy(),
                'total_shifts' => $dayShifts->count(),
                'assigned_shifts' => $dayShifts->whereNotNull('assigned_to')->count(),
                'unassigned_shifts' => $dayShifts->whereNull('assigned_to')->count(),
                'total_hours' => $dayShifts->sum('planned_hours'),
            ];
            $current->addDay();
        }

        return $staffing;
    }

    public function getTotalPlannedHours(): float
    {
        return (float) $this->shifts()->sum('planned_hours');
    }

    public function getCompletionRate(): float
    {
        $total = $this->shifts()->count();
        if ($total === 0) {
            return 100;
        }
        $assigned = $this->shifts()->whereNotNull('assigned_to')->count();

        return round(($assigned / $total) * 100, 1);
    }
}
