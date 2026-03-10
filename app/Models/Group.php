<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $description
 * @property string|null $image
 * @property string $code
 * @property int|null $max_members
 * @property int|null $leader_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read int $members_count
 * @property-read \App\Models\User|null $leader
 * @property-read \App\Models\Pivots\GroupUser|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group active()
 * @method static \Database\Factories\GroupFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereLeaderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereMaxMembers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group withSpace()
 * @mixin \Eloquent
 */
class Group extends Model
{
    use HasFactory, LogsActivity, ClearsCache;

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
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'code',
        'max_members',
        'leader_id',
        'is_active',
        'image',
    ];

    protected $appends = [
        'members_count',
    ];

    protected function casts(): array
    {
        return [
            'max_members' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->using(\App\Models\Pivots\GroupUser::class)
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function getMembersCountAttribute(): int
    {
        // Use users_count from withCount() if available, otherwise fall back to querying
        if (isset($this->attributes['users_count'])) {
            return (int) $this->attributes['users_count'];
        }

        return $this->users()->count();
    }

    public function isAtCapacity(): bool
    {
        return $this->max_members && $this->members_count >= $this->max_members;
    }

    public function canJoin(): bool
    {
        return $this->is_active && ! $this->isAtCapacity();
    }

    public function isMember(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    public function isLeader(User $user): bool
    {
        return $this->leader_id === $user->id;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithSpace($query)
    {
        return $query->where(function ($q): void {
            $q->whereNull('max_members')
                ->orWhereRaw('(SELECT COUNT(*) FROM group_user WHERE group_id = groups.id) < max_members');
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
