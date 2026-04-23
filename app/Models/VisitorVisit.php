<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VisitorVisit extends Model
{
    use ClearsCache, HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'visitor_id',
        'visitable_type',
        'visitable_id',
        'first_visited_at',
        'integration_score',
        'integration_status',
        'notes',
        'invited_by',
    ];

    protected function casts(): array
    {
        return [
            'first_visited_at' => 'date',
            'integration_score' => 'decimal:2',
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

    public function visitable(): MorphTo
    {
        return $this->morphTo();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(VisitorAttendance::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function integrationProgress(): HasMany
    {
        return $this->hasMany(VisitorIntegrationProgress::class);
    }

    public function suggestion(): HasOne
    {
        return $this->hasOne(IntegrationSuggestion::class);
    }

    public function scopeForGroup($query, int $groupId)
    {
        return $query->where('visitable_type', Group::class)
            ->where('visitable_id', $groupId);
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('visitable_type', Department::class)
            ->where('visitable_id', $departmentId);
    }

    public function scopeReady($query)
    {
        return $query->where('integration_status', 'ready');
    }
}
