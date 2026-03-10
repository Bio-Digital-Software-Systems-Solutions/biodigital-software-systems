<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $report_id
 * @property int $version_number
 * @property array<array-key, mixed> $snapshot
 * @property string|null $change_summary
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\DepartmentReport $report
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion forReport(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion latest()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion whereChangeSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion whereSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportVersion whereVersionNumber($value)
 * @mixin \Eloquent
 */
class ReportVersion extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'report_id',
        'version_number',
        'snapshot',
        'change_summary',
        'created_by',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relations
    public function report(): BelongsTo
    {
        return $this->belongsTo(DepartmentReport::class, 'report_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeForReport($q, int $id)
    {
        return $q->where('report_id', $id);
    }

    public function scopeLatest($q)
    {
        return $q->orderByDesc('version_number');
    }

    // Methods
    public function getSnapshotData(string $key = null): mixed
    {
        if ($key === null) {
            return $this->snapshot;
        }
        return data_get($this->snapshot, $key);
    }

    public function compareWith(ReportVersion $other): array
    {
        $changes = [];
        $current = $this->snapshot;
        $previous = $other->snapshot;

        foreach ($current as $key => $value) {
            if (!isset($previous[$key])) {
                $changes[$key] = ['type' => 'added', 'new' => $value];
            } elseif ($previous[$key] !== $value) {
                $changes[$key] = ['type' => 'modified', 'old' => $previous[$key], 'new' => $value];
            }
        }

        foreach ($previous as $key => $value) {
            if (!isset($current[$key])) {
                $changes[$key] = ['type' => 'removed', 'old' => $value];
            }
        }

        return $changes;
    }
}
