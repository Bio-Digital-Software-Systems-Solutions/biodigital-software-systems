<?php

namespace App\Models\Scheduling;

use App\Enums\Scheduling\AbsenceStatus;
use App\Enums\Scheduling\AbsenceType;
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
 * @property string $uuid
 * @property int $user_id
 * @property int|null $department_id
 * @property AbsenceType $type
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int $is_full_day
 * @property bool $is_half_day_start
 * @property bool $is_half_day_end
 * @property string|null $start_time
 * @property string|null $end_time
 * @property numeric|null $days_count
 * @property AbsenceStatus $status
 * @property string|null $reason
 * @property string|null $document_path
 * @property int|null $approved_by
 * @property int|null $interim_user_id
 * @property string|null $interim_notes
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property string|null $documents
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read User|null $approvedBy
 * @property-read User|null $approvedByUser
 * @property-read Department|null $department
 * @property-read string $date_range
 * @property-read bool $is_approved
 * @property-read bool $is_current
 * @property-read bool $is_pending
 * @property-read User|null $interimUser
 * @property-read User $user
 * @method static Builder<static>|Absence active()
 * @method static Builder<static>|Absence approved()
 * @method static Builder<static>|Absence byType(\App\Enums\Scheduling\AbsenceType $type)
 * @method static \Database\Factories\Scheduling\AbsenceFactory factory($count = null, $state = [])
 * @method static Builder<static>|Absence forDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static Builder<static>|Absence forDepartment(int $departmentId)
 * @method static Builder<static>|Absence forUser(int $userId)
 * @method static Builder<static>|Absence newModelQuery()
 * @method static Builder<static>|Absence newQuery()
 * @method static Builder<static>|Absence pending()
 * @method static Builder<static>|Absence query()
 * @method static Builder<static>|Absence upcoming()
 * @method static Builder<static>|Absence whereApprovedAt($value)
 * @method static Builder<static>|Absence whereApprovedBy($value)
 * @method static Builder<static>|Absence whereCreatedAt($value)
 * @method static Builder<static>|Absence whereDaysCount($value)
 * @method static Builder<static>|Absence whereDeletedAt($value)
 * @method static Builder<static>|Absence whereDepartmentId($value)
 * @method static Builder<static>|Absence whereDocumentPath($value)
 * @method static Builder<static>|Absence whereDocuments($value)
 * @method static Builder<static>|Absence whereEndDate($value)
 * @method static Builder<static>|Absence whereEndTime($value)
 * @method static Builder<static>|Absence whereId($value)
 * @method static Builder<static>|Absence whereInterimNotes($value)
 * @method static Builder<static>|Absence whereInterimUserId($value)
 * @method static Builder<static>|Absence whereIsFullDay($value)
 * @method static Builder<static>|Absence whereIsHalfDayEnd($value)
 * @method static Builder<static>|Absence whereIsHalfDayStart($value)
 * @method static Builder<static>|Absence whereReason($value)
 * @method static Builder<static>|Absence whereRejectedAt($value)
 * @method static Builder<static>|Absence whereRejectionReason($value)
 * @method static Builder<static>|Absence whereStartDate($value)
 * @method static Builder<static>|Absence whereStartTime($value)
 * @method static Builder<static>|Absence whereStatus($value)
 * @method static Builder<static>|Absence whereType($value)
 * @method static Builder<static>|Absence whereUpdatedAt($value)
 * @method static Builder<static>|Absence whereUserId($value)
 * @method static Builder<static>|Absence whereUuid($value)
 * @mixin \Eloquent
 */
class Absence extends Model
{
    use HasFactory;

    protected $table = 'employee_absences';

    protected $fillable = [
        'uuid',
        'user_id',
        'department_id',
        'approved_by',
        'interim_user_id',
        'interim_notes',
        'type',
        'status',
        'start_date',
        'end_date',
        'is_half_day_start',
        'is_half_day_end',
        'days_count',
        'reason',
        'rejection_reason',
        'document_path',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'type' => AbsenceType::class,
        'status' => AbsenceStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'is_half_day_start' => 'boolean',
        'is_half_day_end' => 'boolean',
        'days_count' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            if (empty($model->days_count)) {
                $model->days_count = $model->calculateDaysCount();
            }
        });

        static::updating(function ($model): void {
            if ($model->isDirty(['start_date', 'end_date', 'is_half_day_start', 'is_half_day_end'])) {
                $model->days_count = $model->calculateDaysCount();
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Alias for approvedBy relationship (for controller compatibility)
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->approvedBy();
    }

    public function interimUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interim_user_id');
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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AbsenceStatus::PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', AbsenceStatus::APPROVED);
    }

    public function scopeByType(Builder $query, AbsenceType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where(function ($q) use ($start, $end): void {
            $q->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end])
                ->orWhere(function ($q2) use ($start, $end): void {
                    $q2->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $end);
                });
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AbsenceStatus::APPROVED)
            ->where('end_date', '>=', Carbon::today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_date', '>', Carbon::today());
    }

    // Accessors
    public function getDateRangeAttribute(): string
    {
        if ($this->start_date->equalTo($this->end_date)) {
            return $this->start_date->format('d/m/Y');
        }
        return $this->start_date->format('d/m') . ' - ' . $this->end_date->format('d/m/Y');
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === AbsenceStatus::PENDING;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === AbsenceStatus::APPROVED;
    }

    public function getIsCurrentAttribute(): bool
    {
        $today = Carbon::today();
        return $this->start_date <= $today && $this->end_date >= $today;
    }

    // Methods
    public function calculateDaysCount(): float
    {
        $days = $this->start_date->diffInDays($this->end_date) + 1;

        // Subtract weekends if counting only work days
        // For simplicity, we count all days but adjust for half days
        if ($this->is_half_day_start) {
            $days -= 0.5;
        }
        if ($this->is_half_day_end && !$this->start_date->equalTo($this->end_date)) {
            $days -= 0.5;
        }

        return max(0.5, $days);
    }

    public function approve(User $approver, ?string $notes = null): bool
    {
        if (!$this->status->canTransitionTo(AbsenceStatus::APPROVED)) {
            return false;
        }

        $this->update([
            'status' => AbsenceStatus::APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        // Update leave balance if needed
        $this->updateLeaveBalance();

        return true;
    }

    public function reject(User $approver, ?string $reason = null): bool
    {
        if (!$this->status->canTransitionTo(AbsenceStatus::REJECTED)) {
            return false;
        }

        $this->update([
            'status' => AbsenceStatus::REJECTED,
            'approved_by' => $approver->id,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);

        return true;
    }

    public function cancel(): bool
    {
        if (!$this->status->canTransitionTo(AbsenceStatus::CANCELLED)) {
            return false;
        }

        // If was approved, restore leave balance
        if ($this->status === AbsenceStatus::APPROVED) {
            $this->restoreLeaveBalance();
        }

        $this->update(['status' => AbsenceStatus::CANCELLED]);

        return true;
    }

    public function coversDate(Carbon $date): bool
    {
        return $date >= $this->start_date && $date <= $this->end_date;
    }

    public function getDatesArray(): array
    {
        $dates = [];
        $current = $this->start_date->copy();

        while ($current <= $this->end_date) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        return $dates;
    }

    protected function updateLeaveBalance(): void
    {
        $balance = LeaveBalance::firstOrCreate(
            [
                'user_id' => $this->user_id,
                'year' => $this->start_date->year,
                'leave_type' => $this->type->value,
            ],
            [
                'entitled_days' => 0,
                'taken_days' => 0,
                'pending_days' => 0,
                'carried_over' => 0,
            ]
        );

        $balance->decrement('pending_days', $this->days_count);
        $balance->increment('taken_days', $this->days_count);
    }

    protected function restoreLeaveBalance(): void
    {
        $balance = LeaveBalance::where('user_id', $this->user_id)
            ->where('year', $this->start_date->year)
            ->where('leave_type', $this->type->value)
            ->first();

        if ($balance) {
            $balance->decrement('taken_days', $this->days_count);
        }
    }

    public static function hasAbsenceOnDate(int $userId, Carbon $date): bool
    {
        return self::where('user_id', $userId)
            ->where('status', AbsenceStatus::APPROVED)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }
}
