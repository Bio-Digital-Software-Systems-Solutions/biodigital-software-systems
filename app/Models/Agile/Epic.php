<?php

namespace App\Models\Agile;

use App\Enums\Agile\EpicStatus;
use App\Models\Project;
use App\Models\User;
use App\Traits\HasUuid;
use Database\Factories\Agile\EpicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $project_id
 * @property int $owner_id
 * @property string $title
 * @property string|null $description
 * @property string|null $business_value
 * @property EpicStatus $status
 * @property int $priority
 * @property \Illuminate\Support\Carbon|null $target_date
 * @property array<array-key, mixed>|null $labels
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Project $project
 * @property-read User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserStory> $userStories
 */
class Epic extends Model
{
    /** @use HasFactory<EpicFactory> */
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'project_id',
        'owner_id',
        'title',
        'description',
        'business_value',
        'status',
        'priority',
        'target_date',
        'labels',
    ];

    protected $casts = [
        'status' => EpicStatus::class,
        'priority' => 'integer',
        'target_date' => 'date',
        'labels' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): EpicFactory
    {
        return EpicFactory::new();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function userStories(): HasMany
    {
        return $this->hasMany(UserStory::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(WorkItemComment::class, 'commentable');
    }

    public function outgoingLinks(): MorphMany
    {
        return $this->morphMany(WorkItemLink::class, 'source');
    }

    public function incomingLinks(): MorphMany
    {
        return $this->morphMany(WorkItemLink::class, 'target');
    }

    /**
     * Percentage of User Stories in this Epic that are in status `done`.
     */
    public function completionPercentage(): int
    {
        $total = $this->userStories()->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->userStories()->where('status', 'done')->count();

        return (int) round(($done / $total) * 100);
    }
}
