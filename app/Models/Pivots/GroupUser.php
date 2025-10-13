<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

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
