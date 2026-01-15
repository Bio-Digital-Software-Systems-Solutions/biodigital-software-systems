<?php

namespace App\Models\Scheduling;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

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
