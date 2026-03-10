<?php

namespace App\Models\Scheduling;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $description
 * @property string|null $category
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $employees
 * @property-read int|null $employees_count
 * @property-read int $employee_count
 * @method static Builder<static>|Skill active()
 * @method static Builder<static>|Skill byCategory(string $category)
 * @method static Builder<static>|Skill newModelQuery()
 * @method static Builder<static>|Skill newQuery()
 * @method static Builder<static>|Skill query()
 * @method static Builder<static>|Skill requiringCertification()
 * @method static Builder<static>|Skill whereCategory($value)
 * @method static Builder<static>|Skill whereCreatedAt($value)
 * @method static Builder<static>|Skill whereDescription($value)
 * @method static Builder<static>|Skill whereId($value)
 * @method static Builder<static>|Skill whereIsActive($value)
 * @method static Builder<static>|Skill whereName($value)
 * @method static Builder<static>|Skill whereUpdatedAt($value)
 * @method static Builder<static>|Skill whereUuid($value)
 * @mixin \Eloquent
 */
class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'category',
        'requires_certification',
        'certification_validity_months',
        'is_active',
    ];

    protected $casts = [
        'requires_certification' => 'boolean',
        'certification_validity_months' => 'integer',
        'is_active' => 'boolean',
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
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_skills')
            ->withPivot(['proficiency_level', 'certified_at', 'certification_expires_at', 'notes'])
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeRequiringCertification(Builder $query): Builder
    {
        return $query->where('requires_certification', true);
    }

    // Accessors
    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->count();
    }

    // Methods
    public static function getCategories(): array
    {
        return self::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->toArray();
    }
}
