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

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string|null $color
 * @property int $min_staff
 * @property int|null $max_staff
 * @property array<array-key, mixed>|null $required_skills
 * @property numeric|null $hourly_rate
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentPositionNomination> $nominations
 * @property-read int|null $nominations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scheduling\Shift> $shifts
 * @property-read int|null $shifts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition active()
 * @method static \Database\Factories\DepartmentPositionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereHourlyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereMaxStaff($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereMinStaff($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereRequiredSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPosition withoutTrashed()
 * @mixin \Eloquent
 */
class DepartmentPosition extends Model
{
    use ClearsCache, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uuid',
        'department_id',
        'name',
        'code',
        'description',
        'color',
        'min_staff',
        'max_staff',
        'required_skills',
        'hourly_rate',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_staff' => 'integer',
            'max_staff' => 'integer',
            'required_skills' => 'array',
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(\App\Models\Scheduling\Shift::class, 'position_id');
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(DepartmentPositionNomination::class, 'department_position_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
