<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VisitorAttendance extends Model
{
    use ClearsCache, HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'visitor_id',
        'visitor_visit_id',
        'attendable_type',
        'attendable_id',
        'attended_at',
        'status',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
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

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function visitorVisit(): BelongsTo
    {
        return $this->belongsTo(VisitorVisit::class);
    }

    public function attendable(): MorphTo
    {
        return $this->morphTo();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeForVisit($query, int $visitId)
    {
        return $query->where('visitor_visit_id', $visitId);
    }
}
