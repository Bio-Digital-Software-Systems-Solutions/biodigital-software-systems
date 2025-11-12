<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $group_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser whereJoinedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupUser whereUserId($value)
 * @mixin \Eloquent
 */
class GroupUser extends Pivot
{
    protected $table = 'group_user';

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }
}
