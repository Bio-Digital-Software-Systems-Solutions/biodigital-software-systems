<?php

namespace App\Models\Scheduling;

use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $approvedBy
 * @property-read Department|null $department
 * @property-read bool $is_approved
 * @property-read bool $is_complete
 * @property-read float $working_hours
 * @property-read \App\Models\Scheduling\Shift|null $shift
 * @property-read User|null $user
 * @method static Builder<static>|TimeEntry approved()
 * @method static Builder<static>|TimeEntry forDate(\Carbon\Carbon $date)
 * @method static Builder<static>|TimeEntry forDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static Builder<static>|TimeEntry forDepartment(int $departmentId)
 * @method static Builder<static>|TimeEntry forUser(int $userId)
 * @method static Builder<static>|TimeEntry newModelQuery()
 * @method static Builder<static>|TimeEntry newQuery()
 * @method static Builder<static>|TimeEntry pendingApproval()
 * @method static Builder<static>|TimeEntry query()
 * @method static Builder<static>|TimeEntry whereCreatedAt($value)
 * @method static Builder<static>|TimeEntry whereId($value)
 * @method static Builder<static>|TimeEntry whereUpdatedAt($value)
 * @method static Builder<static>|TimeEntry withOvertime()
 * @mixin \Eloquent
 */
class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'shift_id',
        'department_id',
        'date',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
        'total_hours',
        'overtime_hours',
        'break_hours',
        'notes',
        'is_manual_entry',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'total_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'break_hours' => 'decimal:2',
        'is_manual_entry' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::saving(function ($model): void {
            if ($model->clock_in && $model->clock_out) {
                $model->calculateHours();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->whereNull('approved_at')
            ->whereNotNull('clock_out');
    }

    public function scopeWithOvertime(Builder $query): Builder
    {
        return $query->where('overtime_hours', '>', 0);
    }

    // Accessors
    public function getIsCompleteAttribute(): bool
    {
        return $this->clock_in && $this->clock_out;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->approved_at !== null;
    }

    public function getWorkingHoursAttribute(): float
    {
        return $this->total_hours - $this->break_hours;
    }

    // Methods
    protected function calculateHours(): void
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return;
        }

        // Calculate total time
        $totalMinutes = $this->clock_in->diffInMinutes($this->clock_out);

        // Calculate break time
        $breakMinutes = 0;
        if ($this->break_start && $this->break_end) {
            $breakMinutes = $this->break_start->diffInMinutes($this->break_end);
        }

        $this->break_hours = round($breakMinutes / 60, 2);
        $this->total_hours = round(($totalMinutes - $breakMinutes) / 60, 2);

        // Calculate overtime (assuming 8 hours standard day)
        $standardHours = 8;
        if ($this->shift) {
            $standardHours = $this->shift->planned_hours;
        }

        $this->overtime_hours = max(0, $this->total_hours - $standardHours);
    }

    public function clockIn(): bool
    {
        if ($this->clock_in) {
            return false;
        }

        $this->update(['clock_in' => now()]);

        return true;
    }

    public function clockOut(): bool
    {
        if (! $this->clock_in || $this->clock_out) {
            return false;
        }

        $this->update(['clock_out' => now()]);

        return true;
    }

    public function startBreak(): bool
    {
        if (! $this->clock_in || $this->break_start) {
            return false;
        }

        $this->update(['break_start' => now()]);

        return true;
    }

    public function endBreak(): bool
    {
        if (! $this->break_start || $this->break_end) {
            return false;
        }

        $this->update(['break_end' => now()]);

        return true;
    }

    public function approve(User $approver): bool
    {
        if ($this->is_approved || ! $this->is_complete) {
            return false;
        }

        $this->update([
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    public static function getTotalHoursForUser(int $userId, Carbon $start, Carbon $end): float
    {
        return (float) self::where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->sum('total_hours');
    }

    public static function getTotalOvertimeForUser(int $userId, Carbon $start, Carbon $end): float
    {
        return (float) self::where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->sum('overtime_hours');
    }
}
