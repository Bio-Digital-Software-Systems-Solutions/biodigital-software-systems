<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VisitorIntegrationProgress extends Model
{
    use ClearsCache, HasFactory, LogsActivity;

    protected $table = 'visitor_integration_progress';

    protected $fillable = [
        'uuid',
        'visitor_visit_id',
        'step_id',
        'status',
        'progress_value',
        'completed_at',
        'notes',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'progress_value' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function visitorVisit(): BelongsTo
    {
        return $this->belongsTo(VisitorVisit::class);
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(IntegrationPathwayStep::class, 'step_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
