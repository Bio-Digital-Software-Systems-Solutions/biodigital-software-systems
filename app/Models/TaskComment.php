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
 * @property int $task_id
 * @property int $user_id
 * @property string $content
 * @property array<array-key, mixed>|null $mentions
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read TaskComment|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TaskComment> $replies
 * @property-read int|null $replies_count
 * @property-read \App\Models\Task $task
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\TaskCommentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment whereMentions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskComment whereUserId($value)
 * @mixin \Eloquent
 */
class TaskComment extends Model
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
        'task_id',
        'user_id',
        'content',
        'parent_id',
        'mentions',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'mentions' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'parent_id')->orderBy('created_at', 'asc');
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
        $mentions = array_filter($mentions, fn ($id): bool => $id !== $userId);
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
