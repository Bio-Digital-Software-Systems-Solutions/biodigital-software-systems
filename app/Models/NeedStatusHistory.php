<?php

namespace App\Models;

use App\Enums\Need\NeedStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
