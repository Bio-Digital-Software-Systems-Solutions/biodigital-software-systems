<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRole whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectRole extends Model
{
    use LogsActivity, ClearsCache;

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
    //
}
