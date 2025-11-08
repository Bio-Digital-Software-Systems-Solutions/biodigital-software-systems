<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $training_id
 * @property string $title
 * @property string $type
 * @property string|null $duration
 * @property string $url
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Training $training
 * @method static \Database\Factories\TrainingMaterialFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial whereTrainingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingMaterial whereUrl($value)
 * @mixin \Eloquent
 */
class TrainingMaterial extends Model
{
    use HasFactory, LogsActivity, ClearsCache;

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    protected $fillable = [
        'training_id',
        'title',
        'type',
        'duration',
        'url',
        'order',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }
}
