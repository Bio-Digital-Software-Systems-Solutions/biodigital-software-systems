<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\ChatMessage|null $lastMessage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatMessage> $messages
 * @property-read int|null $messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $participants
 * @property-read int|null $participants_count
 * @method static \Database\Factories\ChatRoomFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatRoom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatRoom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatRoom query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatRoom whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatRoom whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatRoom whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatRoom whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatRoom whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatRoom whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ChatRoom extends Model
{
    use HasFactory, HasUuid, LogsActivity, ClearsCache;

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
        'name',
        'type',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The user who created the chat room.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Users participating in this chat room.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_room_user', 'chat_room_id', 'user_id');
    }

    /**
     * Messages in this chat room.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'room_id');
    }

    /**
     * The last message in this chat room.
     */
    public function lastMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'room_id')->latest();
    }
}
