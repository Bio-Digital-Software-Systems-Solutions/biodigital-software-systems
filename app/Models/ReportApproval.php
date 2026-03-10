<?php

namespace App\Models;

use App\Enums\Report\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $report_id
 * @property int $user_id
 * @property int $step
 * @property string $role
 * @property ApprovalStatus $status
 * @property string|null $comments
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read bool $is_pending
 * @property-read string $status_color
 * @property-read string $status_label
 * @property-read \App\Models\DepartmentReport $report
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval byStep(int $step)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval forReport(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval forUser(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportApproval whereUserId($value)
 * @mixin \Eloquent
 */
class ReportApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'report_id',
        'user_id',
        'step',
        'role',
        'status',
        'comments',
        'decided_at',
        'metadata',
    ];

    protected $casts = [
        'status' => ApprovalStatus::class,
        'decided_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'status_label',
        'status_color',
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
    public function report(): BelongsTo
    {
        return $this->belongsTo(DepartmentReport::class, 'report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForReport($q, int $id)
    {
        return $q->where('report_id', $id);
    }

    public function scopePending($q)
    {
        return $q->where('status', ApprovalStatus::PENDING->value);
    }

    public function scopeApproved($q)
    {
        return $q->where('status', ApprovalStatus::APPROVED->value);
    }

    public function scopeRejected($q)
    {
        return $q->where('status', ApprovalStatus::REJECTED->value);
    }

    public function scopeForUser($q, int $id)
    {
        return $q->where('user_id', $id);
    }

    public function scopeByStep($q, int $step)
    {
        return $q->where('step', $step);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === ApprovalStatus::PENDING;
    }

    // Methods
    public function approve(?string $comments = null): self
    {
        $this->status = ApprovalStatus::APPROVED;
        $this->comments = $comments;
        $this->decided_at = now();
        $this->save();
        return $this;
    }

    public function reject(?string $comments = null): self
    {
        $this->status = ApprovalStatus::REJECTED;
        $this->comments = $comments;
        $this->decided_at = now();
        $this->save();
        return $this;
    }
}
