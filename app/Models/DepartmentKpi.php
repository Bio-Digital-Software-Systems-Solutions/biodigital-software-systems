<?php

namespace App\Models;

use App\Enums\Report\TrendDirection;
use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DepartmentKpi extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, ClearsCache;

    protected $fillable = [
        'uuid',
        'department_id',
        'name',
        'description',
        'unit',
        'target_value',
        'warning_threshold',
        'critical_threshold',
        'trend_direction',
        'calculation_method',
        'data_source',
        'is_active',
        'display_order',
        'config',
        'metadata',
    ];

    protected $casts = [
        'target_value' => 'decimal:4',
        'warning_threshold' => 'decimal:4',
        'critical_threshold' => 'decimal:4',
        'trend_direction' => TrendDirection::class,
        'is_active' => 'boolean',
        'config' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'trend_direction_label',
        'current_value',
        'performance_status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
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

    public function values(): HasMany
    {
        return $this->hasMany(DepartmentKpiValue::class, 'kpi_id')->orderByDesc('recorded_at');
    }

    // Scopes
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForDepartment($q, int $id)
    {
        return $q->where('department_id', $id);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('display_order');
    }

    // Accessors
    public function getTrendDirectionLabelAttribute(): string
    {
        return $this->trend_direction->label();
    }

    public function getCurrentValueAttribute(): ?float
    {
        return $this->values()->latest('recorded_at')->first()?->value;
    }

    public function getPerformanceStatusAttribute(): string
    {
        $current = $this->current_value;
        if ($current === null) {
            return 'unknown';
        }

        $isHigherBetter = $this->trend_direction === TrendDirection::HIGHER_IS_BETTER;
        $isLowerBetter = $this->trend_direction === TrendDirection::LOWER_IS_BETTER;

        if ($this->critical_threshold !== null) {
            if (($isHigherBetter && $current < $this->critical_threshold) ||
                ($isLowerBetter && $current > $this->critical_threshold)) {
                return 'critical';
            }
        }

        if ($this->warning_threshold !== null) {
            if (($isHigherBetter && $current < $this->warning_threshold) ||
                ($isLowerBetter && $current > $this->warning_threshold)) {
                return 'warning';
            }
        }

        if ($this->trend_direction === TrendDirection::TARGET_IS_BEST) {
            $deviation = abs($current - $this->target_value) / $this->target_value * 100;
            if ($deviation > 20) {
                return 'critical';
            }
            if ($deviation > 10) {
                return 'warning';
            }
        }

        return 'good';
    }

    // Methods
    public function recordValue(float $value, ?int $reportId = null, ?int $userId = null, ?string $notes = null): DepartmentKpiValue
    {
        return $this->values()->create([
            'value' => $value,
            'recorded_at' => now(),
            'report_id' => $reportId,
            'recorded_by' => $userId ?? auth()->id(),
            'notes' => $notes,
        ]);
    }

    public function getValuesForPeriod(Carbon $start, Carbon $end): \Illuminate\Database\Eloquent\Collection
    {
        return $this->values()
            ->whereBetween('recorded_at', [$start, $end])
            ->orderBy('recorded_at')
            ->get();
    }

    public function calculateTrend(int $periods = 3): array
    {
        $values = $this->values()->take($periods)->get()->reverse();

        if ($values->count() < 2) {
            return ['direction' => 'stable', 'change' => 0, 'percentage' => 0];
        }

        $first = $values->first()->value;
        $last = $values->last()->value;
        $change = $last - $first;
        $percentage = $first != 0 ? ($change / $first) * 100 : 0;

        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            'change' => $change,
            'percentage' => round($percentage, 2),
            'is_positive' => $this->trend_direction->isGood($last, $first, $this->target_value),
        ];
    }

    public function getStatusColor(): string
    {
        return match ($this->performance_status) {
            'good' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'gray',
        };
    }
}
