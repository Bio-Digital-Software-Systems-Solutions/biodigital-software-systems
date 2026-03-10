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
 * @property string $name
 * @property string|null $description
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Training $training
 * @method static \Database\Factories\TrainingTopicFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTopic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTopic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTopic query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTopic whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTopic whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTopic whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTopic whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTopic whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTopic whereTrainingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingTopic whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TrainingTopic extends Model
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
        'name',
        'description',
        'order',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }
}
