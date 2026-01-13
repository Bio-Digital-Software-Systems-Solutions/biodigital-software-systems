<?php

namespace App\Models;

use App\Enums\Report\ActivityCategory;
use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DepartmentActivity extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, ClearsCache;

    protected $fillable = [
        'uuid',
        'department_id',
        'user_id',
        'category',
        'title',
        'description',
        'date',
        'duration_hours',
        'participants',
        'outcomes',
        'metrics',
        'related_project_id',
        'metadata',
    ];

    protected $casts = [
        'category' => ActivityCategory::class,
        'date' => 'date',
        'duration_hours' => 'decimal:2',
        'participants' => 'array',
        'metrics' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'category_label',
        'category_icon',
        'category_color',
        'participant_count',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'related_project_id');
    }

    // Scopes
    public function scopeForDepartment($q, int $id)
    {
        return $q->where('department_id', $id);
    }

    public function scopeForUser($q, int $id)
    {
        return $q->where('user_id', $id);
    }

    public function scopeForPeriod($q, Carbon $start, Carbon $end)
    {
        return $q->whereBetween('date', [$start, $end]);
    }

    public function scopeByCategory($q, ActivityCategory $category)
    {
        return $q->where('category', $category->value);
    }

    public function scopeRecent($q, int $days = 30)
    {
        return $q->where('date', '>=', now()->subDays($days));
    }

    // Accessors
    public function getCategoryLabelAttribute(): string
    {
        return $this->category->label();
    }

    public function getCategoryIconAttribute(): string
    {
        return $this->category->icon();
    }

    public function getCategoryColorAttribute(): string
    {
        return $this->category->color();
    }

    public function getParticipantCountAttribute(): int
    {
        return count($this->participants ?? []);
    }

    // Methods
    public function addParticipant(int $userId): self
    {
        $participants = $this->participants ?? [];
        if (!in_array($userId, $participants)) {
            $participants[] = $userId;
            $this->participants = $participants;
            $this->save();
        }
        return $this;
    }

    public function removeParticipant(int $userId): self
    {
        $participants = $this->participants ?? [];
        $this->participants = array_values(array_filter($participants, fn($id) => $id !== $userId));
        $this->save();
        return $this;
    }
}
