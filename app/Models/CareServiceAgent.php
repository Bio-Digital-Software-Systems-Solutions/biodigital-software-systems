<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareServiceAgent withoutTrashed()
 *
 * @mixin \Eloquent
 */
class CareServiceAgent extends Model
{
    use ClearsCache, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
