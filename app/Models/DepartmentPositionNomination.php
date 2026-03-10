<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property int $department_position_id
 * @property int $user_id
 * @property int|null $nominated_by
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $notes
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Department $department
 * @property-read \App\Models\User|null $nominatedBy
 * @property-read \App\Models\DepartmentPosition $position
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereDepartmentPositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereNominatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPositionNomination withoutTrashed()
 * @mixin \Eloquent
 */
class DepartmentPositionNomination extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uuid',
        'department_id',
        'department_position_id',
        'user_id',
        'nominated_by',
        'start_date',
        'end_date',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
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

    public function position(): BelongsTo
    {
        return $this->belongsTo(DepartmentPosition::class, 'department_position_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nominatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
