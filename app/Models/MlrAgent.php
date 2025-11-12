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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MlrAgent withoutTrashed()
 * @mixin \Eloquent
 */
class MlrAgent extends Model
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
