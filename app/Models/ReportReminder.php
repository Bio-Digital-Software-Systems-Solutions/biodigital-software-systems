<?php

namespace App\Models;

use App\Enums\Report\ReminderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $department_id
 * @property int|null $template_id
 * @property ReminderType $type
 * @property \Illuminate\Support\Carbon $scheduled_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property int $recipient_id
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Department $department
 * @property-read bool $is_pending
 * @property-read bool $is_sent
 * @property-read string $type_label
 * @property-read \App\Models\User $recipient
 * @property-read \App\Models\ReportTemplate|null $template
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder byType(\App\Enums\Report\ReminderType $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder forDepartment(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder scheduledFor($date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder sent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder whereRecipientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder whereScheduledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportReminder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
