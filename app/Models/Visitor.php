<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Visitor extends Model
{
    use ClearsCache, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'gender',
        'date_of_birth',
        'photo',
        'notes',
        'source',
        'first_visit_date',
        'status',
        'user_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'first_visit_date' => 'date',
            'date_of_birth' => 'date',
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

    public function getNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function visits(): HasMany
    {
        return $this->hasMany(VisitorVisit::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(VisitorAttendance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeIntegrated($query)
    {
        return $query->where('status', 'integrated');
    }

    public function scopeReadyForIntegration($query, float $threshold = 80)
    {
        return $query->whereHas('visits', function ($q) use ($threshold): void {
            $q->where('integration_score', '>=', $threshold)
                ->where('integration_status', '!=', 'integrated');
        });
    }
}
