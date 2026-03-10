<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $kpi_id
 * @property int|null $report_id
 * @property numeric $value
 * @property \Illuminate\Support\Carbon $recorded_at
 * @property int|null $recorded_by
 * @property string|null $notes
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\DepartmentKpi $kpi
 * @property-read \App\Models\User|null $recorder
 * @property-read \App\Models\DepartmentReport|null $report
 * @method static \Database\Factories\DepartmentKpiValueFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue forKpi(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue forPeriod($start, $end)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue forReport(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue whereKpiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue whereRecordedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentKpiValue whereValue($value)
 * @mixin \Eloquent
 */
class DepartmentKpiValue extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'kpi_id',
        'report_id',
        'value',
        'recorded_at',
        'recorded_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'recorded_at' => 'date',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relations
    public function kpi(): BelongsTo
    {
        return $this->belongsTo(DepartmentKpi::class, 'kpi_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(DepartmentReport::class, 'report_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Scopes
    public function scopeForKpi($q, int $id)
    {
        return $q->where('kpi_id', $id);
    }

    public function scopeForReport($q, int $id)
    {
        return $q->where('report_id', $id);
    }

    public function scopeForPeriod($q, $start, $end)
    {
        return $q->whereBetween('recorded_at', [$start, $end]);
    }

    // Methods
    public function getFormattedValue(): string
    {
        $kpi = $this->kpi;
        $value = $this->value;

        return match ($kpi->unit) {
            '%', 'percent' => number_format($value, 1) . '%',
            '€', 'EUR' => number_format($value, 2, ',', ' ') . ' €',
            '$', 'USD' => '$' . number_format($value, 2),
            'h', 'hours' => number_format($value, 1) . ' h',
            default => number_format($value, 2) . ' ' . $kpi->unit,
        };
    }
}
