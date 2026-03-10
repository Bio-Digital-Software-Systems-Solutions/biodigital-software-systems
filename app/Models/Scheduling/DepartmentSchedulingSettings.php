<?php

namespace App\Models\Scheduling;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $department_id
 * @property int $default_shift_duration
 * @property int $min_rest_between_shifts
 * @property int $max_hours_per_week
 * @property int $max_hours_per_day
 * @property int $max_consecutive_days
 * @property numeric $overtime_threshold
 * @property bool $allow_self_assignment
 * @property bool $allow_shift_swap
 * @property bool $require_swap_approval
 * @property int $advance_schedule_weeks
 * @property bool $auto_publish_enabled
 * @property string|null $auto_publish_time
 * @property string|null $auto_publish_day
 * @property bool $notifications_enabled
 * @property array<array-key, mixed>|null $notification_settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Department $department
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereAdvanceScheduleWeeks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereAllowSelfAssignment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereAllowShiftSwap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereAutoPublishDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereAutoPublishEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereAutoPublishTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereDefaultShiftDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereMaxConsecutiveDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereMaxHoursPerDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereMaxHoursPerWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereMinRestBetweenShifts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereNotificationSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereNotificationsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereOvertimeThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereRequireSwapApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentSchedulingSettings whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DepartmentSchedulingSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'default_shift_duration',
        'min_rest_between_shifts',
        'max_hours_per_week',
        'max_hours_per_day',
        'max_consecutive_days',
        'overtime_threshold',
        'allow_self_assignment',
        'allow_shift_swap',
        'require_swap_approval',
        'advance_schedule_weeks',
        'auto_publish_enabled',
        'auto_publish_time',
        'auto_publish_day',
        'notifications_enabled',
        'notification_settings',
    ];

    protected $casts = [
        'default_shift_duration' => 'integer',
        'min_rest_between_shifts' => 'integer',
        'max_hours_per_week' => 'integer',
        'max_hours_per_day' => 'integer',
        'max_consecutive_days' => 'integer',
        'overtime_threshold' => 'decimal:2',
        'allow_self_assignment' => 'boolean',
        'allow_shift_swap' => 'boolean',
        'require_swap_approval' => 'boolean',
        'advance_schedule_weeks' => 'integer',
        'auto_publish_enabled' => 'boolean',
        'notifications_enabled' => 'boolean',
        'notification_settings' => 'array',
    ];

    // Relations
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Helpers
    public function isWithinLegalLimits(float $hours, string $period = 'day'): bool
    {
        return match ($period) {
            'day' => $hours <= $this->max_hours_per_day,
            'week' => $hours <= $this->max_hours_per_week,
            default => true,
        };
    }

    public function calculateOvertime(float $hours): float
    {
        return max(0, $hours - $this->overtime_threshold);
    }
}
