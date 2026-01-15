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

        static::creating(function ($model) {
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
        return !empty($this->required_skills);
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
        return $this->shifts()
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->get()
            ->sum('duration_hours');
    }
}
