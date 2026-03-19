<?php

namespace App\Models;

use App\Enums\RoutineStepValidationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RoutineStep extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'routine_id',
        'parent_id',
        'name',
        'description',
        'instructions',
        'duration_minutes',
        'sort_order',
        'is_required',
        'requires_validation',
        'validation_status',
        'validated_by',
        'validated_at',
        'validation_notes',
    ];

    protected $casts = [
        'validation_status' => RoutineStepValidationStatus::class,
        'is_required' => 'boolean',
        'requires_validation' => 'boolean',
        'validated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
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

    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(RoutineAssignee::class);
    }

    public function sops(): HasMany
    {
        return $this->hasMany(RoutineSop::class)->orderBy('sort_order');
    }

    // Actions

    public function validateStep(User $validator, ?string $notes = null): bool
    {
        $this->update([
            'validation_status' => RoutineStepValidationStatus::Validated,
            'validated_by' => $validator->id,
            'validated_at' => now(),
            'validation_notes' => $notes,
        ]);

        return true;
    }

    public function rejectStep(User $validator, string $reason): bool
    {
        $this->update([
            'validation_status' => RoutineStepValidationStatus::Rejected,
            'validated_by' => $validator->id,
            'validated_at' => now(),
            'validation_notes' => $reason,
        ]);

        return true;
    }
}
