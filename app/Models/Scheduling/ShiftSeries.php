<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShiftSeries extends Model
{
    protected $fillable = [
        'uuid',
        'recurrence_type',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'series_id')->orderBy('date');
    }
}
