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

        static::creating(function ($model) {
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
        return 'Semaine ' . $this->week_start->isoWeek() . ' (' .
            $this->week_start->format('d/m') . ' - ' .
            $this->week_end->format('d/m/Y') . ')';
    }

    public function getWeekNumberAttribute(): int
    {
        return $this->week_start->isoWeek();
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
        if (!$this->status->canTransitionTo(ScheduleStatus::PUBLISHED)) {
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
        if (!$this->status->canTransitionTo(ScheduleStatus::LOCKED)) {
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
        return $this->shifts()->sum('planned_hours');
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
