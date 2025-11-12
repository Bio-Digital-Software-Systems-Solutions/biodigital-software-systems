<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $training_id
 * @property string $title
 * @property string|null $description
 * @property int $max_score
 * @property string $due_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\TrainingEvaluationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation whereMaxScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation whereTrainingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEvaluation whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @mixin \Eloquent
 */
class TrainingEvaluation extends Model
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
}
