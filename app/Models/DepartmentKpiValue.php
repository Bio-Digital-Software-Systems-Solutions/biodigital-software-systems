<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
