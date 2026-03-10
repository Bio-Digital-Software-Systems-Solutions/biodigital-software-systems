<?php

namespace App\Models\Scheduling;

use App\Models\Department;
use App\Models\User;
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
 * @property int $year
 * @property string $leave_type
 * @property numeric $entitled_days
 * @property numeric $taken_days
 * @property numeric $pending_days
 * @property numeric $carried_over
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Department|null $department
 * @property-read float $remaining_days
 * @property-read float $total_entitled
 * @property-read float $usage_percentage
 * @property-read float $used_days
 * @property-read User $user
 * @method static Builder<static>|LeaveBalance byType(string $leaveType)
 * @method static \Database\Factories\Scheduling\LeaveBalanceFactory factory($count = null, $state = [])
 * @method static Builder<static>|LeaveBalance forUser(int $userId)
 * @method static Builder<static>|LeaveBalance forYear(int $year)
 * @method static Builder<static>|LeaveBalance newModelQuery()
 * @method static Builder<static>|LeaveBalance newQuery()
 * @method static Builder<static>|LeaveBalance query()
 * @method static Builder<static>|LeaveBalance whereCarriedOver($value)
 * @method static Builder<static>|LeaveBalance whereCreatedAt($value)
 * @method static Builder<static>|LeaveBalance whereDepartmentId($value)
 * @method static Builder<static>|LeaveBalance whereEntitledDays($value)
 * @method static Builder<static>|LeaveBalance whereId($value)
 * @method static Builder<static>|LeaveBalance whereLeaveType($value)
 * @method static Builder<static>|LeaveBalance wherePendingDays($value)
 * @method static Builder<static>|LeaveBalance whereTakenDays($value)
 * @method static Builder<static>|LeaveBalance whereUpdatedAt($value)
 * @method static Builder<static>|LeaveBalance whereUserId($value)
 * @method static Builder<static>|LeaveBalance whereUuid($value)
 * @method static Builder<static>|LeaveBalance whereYear($value)
 * @mixin \Eloquent
 */
class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'department_id',
        'year',
        'leave_type',
        'entitled_days',
        'taken_days',
        'pending_days',
        'carried_over',
    ];

    protected $casts = [
        'year' => 'integer',
        'entitled_days' => 'decimal:2',
        'taken_days' => 'decimal:2',
        'pending_days' => 'decimal:2',
        'carried_over' => 'decimal:2',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    public function scopeByType(Builder $query, string $leaveType): Builder
    {
        return $query->where('leave_type', $leaveType);
    }

    // Accessors
    public function getRemainingDaysAttribute(): float
    {
        return $this->entitled_days + $this->carried_over - $this->taken_days - $this->pending_days;
    }

    public function getUsedDaysAttribute(): float
    {
        return $this->taken_days + $this->pending_days;
    }

    public function getTotalEntitledAttribute(): float
    {
        return $this->entitled_days + $this->carried_over;
    }

    public function getUsagePercentageAttribute(): float
    {
        $total = $this->total_entitled;
        if ($total <= 0) {
            return 0;
        }
        return round(($this->used_days / $total) * 100, 1);
    }

    // Methods
    public function hasEnoughBalance(float $days): bool
    {
        return $this->remaining_days >= $days;
    }

    public function reserve(float $days): bool
    {
        if (!$this->hasEnoughBalance($days)) {
            return false;
        }

        $this->increment('pending_days', $days);
        return true;
    }

    public function confirm(float $days): bool
    {
        if ($this->pending_days < $days) {
            return false;
        }

        $this->decrement('pending_days', $days);
        $this->increment('taken_days', $days);
        return true;
    }

    public function release(float $days): bool
    {
        if ($this->pending_days < $days) {
            return false;
        }

        $this->decrement('pending_days', $days);
        return true;
    }

    public function restore(float $days): bool
    {
        if ($this->taken_days < $days) {
            return false;
        }

        $this->decrement('taken_days', $days);
        return true;
    }

    public static function getForUser(int $userId, int $year): array
    {
        return self::where('user_id', $userId)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type')
            ->toArray();
    }

    public static function carryOverFromPreviousYear(int $userId, int $fromYear, float $maxCarryOver = 5): void
    {
        $previousBalances = self::where('user_id', $userId)
            ->where('year', $fromYear)
            ->get();

        foreach ($previousBalances as $balance) {
            $remaining = $balance->remaining_days;
            $toCarryOver = min($remaining, $maxCarryOver);

            if ($toCarryOver > 0) {
                self::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'year' => $fromYear + 1,
                        'leave_type' => $balance->leave_type,
                    ],
                    [
                        'carried_over' => $toCarryOver,
                    ]
                );
            }
        }
    }
}
