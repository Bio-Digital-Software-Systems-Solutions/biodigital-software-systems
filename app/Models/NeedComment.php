<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $need_id
 * @property int $user_id
 * @property string $content
 * @property bool $is_internal
 * @property int|null $parent_id
 * @property array<array-key, mixed>|null $mentions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\DepartmentNeed $need
 * @property-read NeedComment|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NeedComment> $replies
 * @property-read int|null $replies_count
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\NeedCommentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereIsInternal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereMentions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereNeedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedComment withoutTrashed()
 * @mixin \Eloquent
 */
class NeedComment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uuid',
        'need_id',
        'user_id',
        'content',
        'is_internal',
        'parent_id',
        'mentions',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'mentions' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function need(): BelongsTo
    {
        return $this->belongsTo(DepartmentNeed::class, 'need_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    public function getMentionedUsers(): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($this->mentions)) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return User::whereIn('id', $this->mentions)->get();
    }

    public function addMention(int $userId): self
    {
        $mentions = $this->mentions ?? [];
        if (! in_array($userId, $mentions)) {
            $mentions[] = $userId;
            $this->update(['mentions' => $mentions]);
        }

        return $this;
    }

    public function removeMention(int $userId): self
    {
        $mentions = $this->mentions ?? [];
        $mentions = array_filter($mentions, fn ($id): bool => $id !== $userId);
        $this->update(['mentions' => array_values($mentions)]);

        return $this;
    }

    public function reply(int $userId, string $content, ?bool $isInternal = null): self
    {
        return self::create([
            'need_id' => $this->need_id,
            'user_id' => $userId,
            'content' => $content,
            'is_internal' => $isInternal ?? $this->is_internal,
            'parent_id' => $this->id,
        ]);
    }
}
