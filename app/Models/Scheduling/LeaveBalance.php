<?php

namespace App\Models\Scheduling;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
