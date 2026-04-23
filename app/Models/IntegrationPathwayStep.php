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

class IntegrationPathwayStep extends Model
{
    use ClearsCache, HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'template_id',
        'name',
        'description',
        'order_index',
        'type',
        'criteria',
        'weight',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'is_required' => 'boolean',
            'order_index' => 'integer',
            'weight' => 'integer',
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(IntegrationPathwayTemplate::class, 'template_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(VisitorIntegrationProgress::class, 'step_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
