<?php

namespace App\Models;

use App\Enums\Report\ReminderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReportReminder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'department_id',
        'template_id',
        'type',
        'scheduled_at',
        'sent_at',
        'recipient_id',
        'metadata',
    ];

    protected $casts = [
        'type' => ReminderType::class,
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'type_label',
        'is_sent',
        'is_pending',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relations
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // Scopes
    public function scopeForDepartment($q, int $id)
    {
        return $q->where('department_id', $id);
    }

    public function scopePending($q)
    {
        return $q->whereNull('sent_at')->where('scheduled_at', '<=', now());
    }

    public function scopeSent($q)
    {
        return $q->whereNotNull('sent_at');
    }

    public function scopeScheduledFor($q, $date)
    {
        return $q->whereDate('scheduled_at', $date);
    }

    public function scopeByType($q, ReminderType $type)
    {
        return $q->where('type', $type->value);
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return $this->type->label();
    }

    public function getIsSentAttribute(): bool
    {
        return $this->sent_at !== null;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->sent_at === null && $this->scheduled_at->isPast();
    }

    // Methods
    public function markAsSent(): self
    {
        $this->sent_at = now();
        $this->save();
        return $this;
    }

    public function reschedule(\DateTime $newDate): self
    {
        $this->scheduled_at = $newDate;
        $this->sent_at = null;
        $this->save();
        return $this;
    }
}
