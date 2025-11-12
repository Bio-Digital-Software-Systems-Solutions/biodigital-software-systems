<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pastor withoutTrashed()
 * @mixin \Eloquent
 */
class Pastor extends Model
{
    use HasFactory, SoftDeletes, ClearsCache;

    protected $fillable = [
        'uuid',
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    // Boot method to auto-generate UUID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Route key name for UUID-based routing
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
