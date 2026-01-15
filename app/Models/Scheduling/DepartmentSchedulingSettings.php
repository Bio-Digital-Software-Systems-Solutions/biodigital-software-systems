<?php

namespace App\Models\Scheduling;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
