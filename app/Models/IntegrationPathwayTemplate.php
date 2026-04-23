<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class IntegrationPathwayTemplate extends Model
{
    use ClearsCache, HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'target_type',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
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

    public function steps(): HasMany
    {
        return $this->hasMany(IntegrationPathwayStep::class, 'template_id');
    }

    public function orderedSteps(): HasMany
    {
        return $this->hasMany(IntegrationPathwayStep::class, 'template_id')->orderBy('order_index');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForType($query, ?string $type)
    {
        return $query->where(function ($q) use ($type): void {
            $q->where('target_type', $type)
                ->orWhereNull('target_type');
        });
    }
}
