<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $code
 * @property string|null $native_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\SpokenLanguageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpokenLanguage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpokenLanguage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpokenLanguage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpokenLanguage whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpokenLanguage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpokenLanguage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpokenLanguage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpokenLanguage whereNativeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpokenLanguage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpokenLanguage whereUuid($value)
 * @mixin \Eloquent
 */
class SpokenLanguage extends Model
{
    /** @use HasFactory<\Database\Factories\SpokenLanguageFactory> */
    use HasFactory, LogsActivity;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'code',
        'native_name',
    ];

    /**
     * The users that speak this language.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'spoken_language_user')
            ->withPivot('level')
            ->withTimestamps();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
