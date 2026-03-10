<?php

namespace App\Models;

use App\Enums\Report\TrendDirection;
use App\Traits\ClearsCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property string $name
 * @property string|null $description
 * @property string $unit
 * @property numeric $target_value
 * @property numeric|null $warning_threshold
 * @property numeric|null $critical_threshold
 * @property TrendDirection $trend_direction
 * @property string|null $calculation_method
 * @property string|null $data_source
 * @property bool $is_active
 * @property int $display_order
 * @property array<array-key, mixed>|null $config
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Department $department
 * @property-read float|null $current_value
 * @property-read string $performance_status
 * @property-read string $trend_direction_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentKpiValue> $values
 * @property-read int|null $values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi active()
 * @method static \Database\Factories\DepartmentKpiFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi forDepartment(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereCalculationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereCriticalThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereDataSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereTargetValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereTrendDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi whereWarningThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpi withoutTrashed()
 * @mixin \Eloquent
 */
class DepartmentKpi extends Model
{
    use ClearsCache, HasFactory, LogsActivity, SoftDeletes;

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
        static::creating(fn ($m) => $m->uuid ??= (string) Str::uuid());
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
        $value = $this->values()->latest('recorded_at')->first()?->value;

        return $value !== null ? (float) $value : null;
    }

    public function getPerformanceStatusAttribute(): string
    {
        $current = $this->current_value;
        if ($current === null) {
            return 'unknown';
        }

        $isHigherBetter = $this->trend_direction === TrendDirection::HIGHER_IS_BETTER;
        $isLowerBetter = $this->trend_direction === TrendDirection::LOWER_IS_BETTER;

        if ($this->critical_threshold !== null && ($isHigherBetter && $current < $this->critical_threshold || $isLowerBetter && $current > $this->critical_threshold)) {
            return 'critical';
        }

        if ($this->warning_threshold !== null && ($isHigherBetter && $current < $this->warning_threshold || $isLowerBetter && $current > $this->warning_threshold)) {
            return 'warning';
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
