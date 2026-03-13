<?php

namespace App\Models\Scheduling;

use App\Enums\Scheduling\ShiftStatus;
use App\Enums\Scheduling\ShiftType;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $weekly_schedule_id
 * @property int $department_id
 * @property int|null $position_id
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon $date
 * @property string $start_time
 * @property string $end_time
 * @property int $break_duration
 * @property ShiftType $type
 * @property ShiftStatus $status
 * @property string|null $title
 * @property string|null $description
 * @property string|null $location
 * @property string|null $color
 * @property int $min_employees
 * @property int $max_employees
 * @property array<array-key, mixed>|null $required_skills
 * @property numeric|null $hourly_rate
 * @property bool $is_overtime
 * @property bool $requires_approval
 * @property int|null $assigned_by
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property \Illuminate\Support\Carbon|null $checked_in_at
 * @property \Illuminate\Support\Carbon|null $checked_out_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $assignedBy
 * @property-read Department $department
 * @property-read bool $can_check_in
 * @property-read bool $can_check_out
 * @property-read float $duration_hours
 * @property-read Carbon $end_date_time
 * @property-read bool $is_future
 * @property-read bool $is_past
 * @property-read bool $is_today
 * @property-read Carbon $start_date_time
 * @property-read string $time_range
 * @property-read \App\Models\Scheduling\SchedulingPosition|null $position
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scheduling\ShiftSwapRequest> $swapRequests
 * @property-read int|null $swap_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scheduling\ShiftTask> $tasks
 * @property-read int|null $tasks_count
 * @property-read \App\Models\Scheduling\TimeEntry|null $timeEntry
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 * @property-read \App\Models\Scheduling\WeeklySchedule $weeklySchedule
 *
 * @method static Builder<static>|Shift active()
 * @method static Builder<static>|Shift assigned()
 * @method static Builder<static>|Shift byStatus(\App\Enums\Scheduling\ShiftStatus $status)
 * @method static Builder<static>|Shift byType(\App\Enums\Scheduling\ShiftType $type)
 * @method static \Database\Factories\Scheduling\ShiftFactory factory($count = null, $state = [])
 * @method static Builder<static>|Shift forDate(\Carbon\Carbon $date)
 * @method static Builder<static>|Shift forDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static Builder<static>|Shift forUser(int $userId)
 * @method static Builder<static>|Shift newModelQuery()
 * @method static Builder<static>|Shift newQuery()
 * @method static Builder<static>|Shift onlyTrashed()
 * @method static Builder<static>|Shift query()
 * @method static Builder<static>|Shift today()
 * @method static Builder<static>|Shift unassigned()
 * @method static Builder<static>|Shift upcoming()
 * @method static Builder<static>|Shift whereAssignedAt($value)
 * @method static Builder<static>|Shift whereAssignedBy($value)
 * @method static Builder<static>|Shift whereBreakDuration($value)
 * @method static Builder<static>|Shift whereCheckedInAt($value)
 * @method static Builder<static>|Shift whereCheckedOutAt($value)
 * @method static Builder<static>|Shift whereColor($value)
 * @method static Builder<static>|Shift whereCreatedAt($value)
 * @method static Builder<static>|Shift whereDate($value)
 * @method static Builder<static>|Shift whereDeletedAt($value)
 * @method static Builder<static>|Shift whereDepartmentId($value)
 * @method static Builder<static>|Shift whereDescription($value)
 * @method static Builder<static>|Shift whereEndTime($value)
 * @method static Builder<static>|Shift whereHourlyRate($value)
 * @method static Builder<static>|Shift whereId($value)
 * @method static Builder<static>|Shift whereIsOvertime($value)
 * @method static Builder<static>|Shift whereLocation($value)
 * @method static Builder<static>|Shift whereMaxEmployees($value)
 * @method static Builder<static>|Shift whereMinEmployees($value)
 * @method static Builder<static>|Shift whereNotes($value)
 * @method static Builder<static>|Shift wherePositionId($value)
 * @method static Builder<static>|Shift whereRequiredSkills($value)
 * @method static Builder<static>|Shift whereRequiresApproval($value)
 * @method static Builder<static>|Shift whereStartTime($value)
 * @method static Builder<static>|Shift whereStatus($value)
 * @method static Builder<static>|Shift whereTitle($value)
 * @method static Builder<static>|Shift whereType($value)
 * @method static Builder<static>|Shift whereUpdatedAt($value)
 * @method static Builder<static>|Shift whereUserId($value)
 * @method static Builder<static>|Shift whereUuid($value)
 * @method static Builder<static>|Shift whereWeeklyScheduleId($value)
 * @method static Builder<static>|Shift withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Shift withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'weekly_schedule_id',
        'series_id',
        'department_id',
        'position_id',
        'user_id',
        'date',
        'type',
        'status',
        'start_time',
        'end_time',
        'break_duration',
        'title',
        'description',
        'location',
        'color',
        'min_employees',
        'max_employees',
        'required_skills',
        'hourly_rate',
        'is_overtime',
        'requires_approval',
        'assigned_by',
        'assigned_at',
        'notes',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => ShiftType::class,
        'status' => ShiftStatus::class,
        'break_duration' => 'integer',
        'required_skills' => 'array',
        'hourly_rate' => 'decimal:2',
        'is_overtime' => 'boolean',
        'requires_approval' => 'boolean',
        'min_employees' => 'integer',
        'max_employees' => 'integer',
        'assigned_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    protected $appends = ['duration_hours'];

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
    public function series(): BelongsTo
    {
        return $this->belongsTo(ShiftSeries::class, 'series_id');
    }

    public function weeklySchedule(): BelongsTo
    {
        return $this->belongsTo(WeeklySchedule::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_user')
            ->withPivot('time_slot')
            ->withTimestamps();
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(SchedulingPosition::class, 'position_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ShiftTask::class)->orderBy('sort_order');
    }

    public function swapRequests(): HasMany
    {
        return $this->hasMany(ShiftSwapRequest::class, 'requester_shift_id');
    }

    public function timeEntry(): HasOne
    {
        return $this->hasOne(TimeEntry::class);
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    public function scopeAssigned(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeByType(Builder $query, ShiftType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus(Builder $query, ShiftStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('date', Carbon::today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('date', '>=', Carbon::today());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ShiftStatus::PUBLISHED,
            ShiftStatus::CONFIRMED,
            ShiftStatus::IN_PROGRESS,
        ]);
    }

    // Accessors
    public function getTimeRangeAttribute(): string
    {
        return $this->start_time.' - '.$this->end_time;
    }

    public function getIsPastAttribute(): bool
    {
        return $this->date < Carbon::today();
    }

    public function getIsTodayAttribute(): bool
    {
        return $this->date->isToday();
    }

    public function getIsFutureAttribute(): bool
    {
        return $this->date > Carbon::today();
    }

    public function getCanCheckInAttribute(): bool
    {
        if (! $this->is_today || ! $this->user_id) {
            return false;
        }
        if ($this->checked_in_at) {
            return false;
        }

        return in_array($this->status, [ShiftStatus::PUBLISHED, ShiftStatus::CONFIRMED]);
    }

    public function getDurationHoursAttribute(): float
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        // Handle overnight shifts
        if ($end <= $start) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);
        $breakMinutes = $this->break_duration ?? 0;

        return round(($totalMinutes - $breakMinutes) / 60, 2);
    }

    public function getCanCheckOutAttribute(): bool
    {
        return $this->checked_in_at && ! $this->checked_out_at;
    }

    public function getStartDateTimeAttribute(): Carbon
    {
        return Carbon::parse($this->date->format('Y-m-d').' '.$this->start_time);
    }

    public function getEndDateTimeAttribute(): Carbon
    {
        $end = Carbon::parse($this->date->format('Y-m-d').' '.$this->end_time);
        // Handle overnight shifts
        if ($end <= $this->start_date_time) {
            $end->addDay();
        }

        return $end;
    }

    // Methods
    public function transitionTo(ShiftStatus $newStatus): bool
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $this->update(['status' => $newStatus]);

        return true;
    }

    public function assign(User $user): bool
    {
        if ($this->user_id) {
            return false;
        }

        $this->update(['user_id' => $user->id]);

        return true;
    }

    public function unassign(): bool
    {
        if (! $this->user_id) {
            return false;
        }

        $this->update(['user_id' => null]);

        return true;
    }

    public function checkIn(): bool
    {
        if (! $this->can_check_in) {
            return false;
        }

        $this->update([
            'checked_in_at' => now(),
            'status' => ShiftStatus::IN_PROGRESS,
        ]);

        return true;
    }

    public function checkOut(): bool
    {
        if (! $this->can_check_out) {
            return false;
        }

        $this->update([
            'checked_out_at' => now(),
            'status' => ShiftStatus::COMPLETED,
        ]);

        return true;
    }

    public function conflictsWith(Shift $other): bool
    {
        if ($this->date->format('Y-m-d') !== $other->date->format('Y-m-d')) {
            return false;
        }

        $thisStart = $this->start_date_time;
        $thisEnd = $this->end_date_time;
        $otherStart = $other->start_date_time;
        $otherEnd = $other->end_date_time;

        return $thisStart < $otherEnd && $thisEnd > $otherStart;
    }

    public function getActualHours(): ?float
    {
        if (! $this->checked_in_at || ! $this->checked_out_at) {
            return null;
        }

        $minutes = $this->checked_in_at->diffInMinutes($this->checked_out_at);
        $breakMinutes = $this->break_duration ?? 0;

        return round(($minutes - $breakMinutes) / 60, 2);
    }

    public function getTasksProgress(): array
    {
        $tasks = $this->tasks;
        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 100,
        ];
    }
}
