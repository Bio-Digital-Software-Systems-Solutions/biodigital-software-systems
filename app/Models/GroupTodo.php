<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GroupTodo extends Model
{
    use ClearsCache, HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'group_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

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

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function complete(int $userId): void
    {
        $this->update([
            'status' => 'completed',
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);
    }

    public function start(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => 'pending',
            'completed_by' => null,
            'completed_at' => null,
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! in_array($this->status, ['completed', 'cancelled']);
    }

    public function scopeForGroup($query, int $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }
}
