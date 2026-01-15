<?php

namespace App\Models\Scheduling;

use App\Enums\Scheduling\ShiftTaskStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShiftTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'shift_id',
        'title',
        'description',
        'status',
        'sort_order',
        'estimated_minutes',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'status' => ShiftTaskStatus::class,
        'sort_order' => 'integer',
        'estimated_minutes' => 'integer',
        'completed_at' => 'datetime',
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
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // Scopes
    public function scopeByStatus(Builder $query, ShiftTaskStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ShiftTaskStatus::TODO,
            ShiftTaskStatus::IN_PROGRESS,
            ShiftTaskStatus::BLOCKED,
        ]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ShiftTaskStatus::COMPLETED);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }
        return $this->shift && $this->shift->date < now()->startOfDay();
    }

    // Methods
    public function complete(User $user): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        $this->update([
            'status' => ShiftTaskStatus::COMPLETED,
            'completed_at' => now(),
            'completed_by' => $user->id,
        ]);

        return true;
    }

    public function start(): bool
    {
        if ($this->status !== ShiftTaskStatus::TODO) {
            return false;
        }

        $this->update(['status' => ShiftTaskStatus::IN_PROGRESS]);
        return true;
    }

    public function block(): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        $this->update(['status' => ShiftTaskStatus::BLOCKED]);
        return true;
    }

    public function cancel(): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        $this->update(['status' => ShiftTaskStatus::CANCELLED]);
        return true;
    }
}
