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
 * @property string $category
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill category(string $category)
 * @method static \Database\Factories\ProfileSkillFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill hard()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill soft()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill technical()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSkill whereUuid($value)
 * @mixin \Eloquent
 */
class ProfileSkill extends Model
{
    /** @use HasFactory<\Database\Factories\ProfileSkillFactory> */
    use HasFactory, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'profile_skills';

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
        'category',
    ];

    /**
     * The users that have this skill.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'profile_skill_user')
            ->withPivot('level')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include skills of a given category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include soft skills.
     */
    public function scopeSoft($query)
    {
        return $query->where('category', 'soft');
    }

    /**
     * Scope a query to only include hard skills.
     */
    public function scopeHard($query)
    {
        return $query->where('category', 'hard');
    }

    /**
     * Scope a query to only include technical skills.
     */
    public function scopeTechnical($query)
    {
        return $query->where('category', 'technical');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
