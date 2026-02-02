<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ProjectComment|null $parent
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectComment> $replies
 * @property-read int|null $replies_count
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectComment whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectComment whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectComment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectComment whereUserId($value)
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 *
 * @mixin \Eloquent
 */
class ProjectComment extends Model
{
    use ClearsCache, HasFactory, LogsActivity;

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'project_id',
        'user_id',
        'parent_id',
        'content',
        'mentions',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'mentions' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ProjectComment::class, 'parent_id')->with('user', 'replies');
    }

    /**
     * Get mentioned users collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getMentionedUsers(): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($this->mentions)) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return User::whereIn('id', $this->mentions)->get();
    }

    /**
     * Add a mention to the comment.
     */
    public function addMention(int $userId): self
    {
        $mentions = $this->mentions ?? [];
        if (! in_array($userId, $mentions)) {
            $mentions[] = $userId;
            $this->update(['mentions' => $mentions]);
        }

        return $this;
    }

    /**
     * Remove a mention from the comment.
     */
    public function removeMention(int $userId): self
    {
        $mentions = $this->mentions ?? [];
        $mentions = array_filter($mentions, fn ($id) => $id !== $userId);
        $this->update(['mentions' => array_values($mentions)]);

        return $this;
    }

    /**
     * Check if a user is mentioned in the comment.
     */
    public function hasMention(int $userId): bool
    {
        return in_array($userId, $this->mentions ?? []);
    }

    /**
     * Check if the comment is a reply.
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Check if the comment has replies.
     */
    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }
}
