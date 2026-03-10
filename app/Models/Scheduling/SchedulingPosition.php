<?php

namespace App\Models\Scheduling;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property string $name
 * @property string|null $description
 * @property string|null $color
 * @property numeric|null $hourly_rate
 * @property array<array-key, mixed>|null $required_skills
 * @property int $min_experience_months
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Department $department
 * @property-read bool $has_required_skills
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scheduling\Shift> $shifts
 * @property-read int|null $shifts_count
 * @method static Builder<static>|SchedulingPosition active()
 * @method static Builder<static>|SchedulingPosition forDepartment(int $departmentId)
 * @method static Builder<static>|SchedulingPosition newModelQuery()
 * @method static Builder<static>|SchedulingPosition newQuery()
 * @method static Builder<static>|SchedulingPosition onlyTrashed()
 * @method static Builder<static>|SchedulingPosition ordered()
 * @method static Builder<static>|SchedulingPosition query()
 * @method static Builder<static>|SchedulingPosition whereColor($value)
 * @method static Builder<static>|SchedulingPosition whereCreatedAt($value)
 * @method static Builder<static>|SchedulingPosition whereDeletedAt($value)
 * @method static Builder<static>|SchedulingPosition whereDepartmentId($value)
 * @method static Builder<static>|SchedulingPosition whereDescription($value)
 * @method static Builder<static>|SchedulingPosition whereHourlyRate($value)
 * @method static Builder<static>|SchedulingPosition whereId($value)
 * @method static Builder<static>|SchedulingPosition whereIsActive($value)
 * @method static Builder<static>|SchedulingPosition whereMinExperienceMonths($value)
 * @method static Builder<static>|SchedulingPosition whereName($value)
 * @method static Builder<static>|SchedulingPosition whereRequiredSkills($value)
 * @method static Builder<static>|SchedulingPosition whereSortOrder($value)
 * @method static Builder<static>|SchedulingPosition whereUpdatedAt($value)
 * @method static Builder<static>|SchedulingPosition whereUuid($value)
 * @method static Builder<static>|SchedulingPosition withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|SchedulingPosition withoutTrashed()
 * @mixin \Eloquent
 */
class SchedulingPosition extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'scheduling_positions';

    protected $fillable = [
        'uuid',
        'department_id',
        'name',
        'description',
        'color',
        'hourly_rate',
        'required_skills',
        'min_experience_months',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'required_skills' => 'array',
        'min_experience_months' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relations
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'position_id');
    }

    // Scopes
    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors
    public function getHasRequiredSkillsAttribute(): bool
    {
        return ! empty($this->required_skills);
    }

    // Methods
    public function getActiveShiftsCount(): int
    {
        return $this->shifts()
            ->whereIn('status', ['published', 'confirmed', 'in_progress'])
            ->count();
    }

    public function getTotalHoursThisWeek(): float
    {
        return (float) $this->shifts()
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->get()
            ->sum('duration_hours');
    }
}
