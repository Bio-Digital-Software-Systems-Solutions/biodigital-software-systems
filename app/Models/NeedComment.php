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

class NeedComment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

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

        static::creating(function (self $model) {
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
            return collect();
        }

        return User::whereIn('id', $this->mentions)->get();
    }

    public function addMention(int $userId): self
    {
        $mentions = $this->mentions ?? [];
        if (!in_array($userId, $mentions)) {
            $mentions[] = $userId;
            $this->update(['mentions' => $mentions]);
        }

        return $this;
    }

    public function removeMention(int $userId): self
    {
        $mentions = $this->mentions ?? [];
        $mentions = array_filter($mentions, fn($id) => $id !== $userId);
        $this->update(['mentions' => array_values($mentions)]);

        return $this;
    }

    public function reply(int $userId, string $content, bool $isInternal = null): self
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
