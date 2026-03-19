<?php

namespace App\Models;

use App\Enums\RoutineAssigneeRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutineAssignee extends Model
{
    use HasFactory;

    protected $fillable = [
        'routine_id',
        'routine_step_id',
        'user_id',
        'role',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'role' => RoutineAssigneeRole::class,
        'assigned_at' => 'datetime',
    ];

    // Relations

    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    public function routineStep(): BelongsTo
    {
        return $this->belongsTo(RoutineStep::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
