<?php

namespace App\Models;

use App\Enums\Need\NeedStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $need_id
 * @property int $changed_by
 * @property NeedStatus|null $from_status
 * @property NeedStatus $to_status
 * @property string|null $reason
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User $changedBy
 * @property-read \App\Models\DepartmentNeed $need
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory whereChangedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory whereFromStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory whereNeedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory whereToStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeedStatusHistory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NeedStatusHistory extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'need_status_history';

    protected $fillable = [
        'need_id',
        'changed_by',
        'from_status',
        'to_status',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'from_status' => NeedStatus::class,
        'to_status' => NeedStatus::class,
        'metadata' => 'array',
    ];

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

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getFromStatusLabel(): ?string
    {
        return $this->from_status?->label();
    }

    public function getToStatusLabel(): string
    {
        return $this->to_status->label();
    }

    public function getFromStatusColor(): ?string
    {
        return $this->from_status?->color();
    }

    public function getToStatusColor(): string
    {
        return $this->to_status->color();
    }
}
