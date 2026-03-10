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

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property int $user_id
 * @property ActivityCategory $category
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $date
 * @property numeric|null $duration_hours
 * @property array<array-key, mixed>|null $participants
 * @property string|null $outcomes
 * @property array<array-key, mixed>|null $metrics
 * @property int|null $related_project_id
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Department $department
 * @property-read string $category_color
 * @property-read string $category_icon
 * @property-read string $category_label
 * @property-read int $participant_count
 * @property-read \App\Models\Project|null $relatedProject
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity byCategory(\App\Enums\Report\ActivityCategory $category)
 * @method static \Database\Factories\DepartmentActivityFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity forDepartment(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity forPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity forUser(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity recent(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereDurationHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereMetrics($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereOutcomes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereParticipants($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereRelatedProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentActivity withoutTrashed()
 * @mixin \Eloquent
 */
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
        static::creating(fn($m) => $m->uuid ??= (string) Str::uuid());
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
        $this->participants = array_values(array_filter($participants, fn($id): bool => $id !== $userId));
        $this->save();
        return $this;
    }
}
